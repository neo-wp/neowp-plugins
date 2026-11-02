import { neoError, neoWarn } from "./_global--log.js";
import { fitProtocolToFetchImgUrl, addCacheBust } from "./_global--url-helper.js";
import { DomNodeHelper, escapeHtml, escapeInlineJsString } from "./_global--dom-node-helper.js";
import { extractJson } from "./_global--extract-json.js";
import { pluginUrl, uploadsUrl } from "./_global-plugin-and-uploads-url.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { neo__ } from "./_global--translation.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { isNeoDrawImage } from "./_global-image-metadata.js";
import { getFileType } from "./_global-media-file-type.js";
import { pdfFirstPageToWebpFile } from "./_global-pdf-to-webp-converter.js";
import { formatKb } from "./_global--math.js";
import { observeOnce } from "./_global--observer.js";
import { getLibraryTable, getRowNode } from "./neo-library--globals.js";
import { getSortMode, isNewNeoDrawImageRow } from "./neo-library--tools.js";

export const fillRowsObserver = new IntersectionObserver((entries) => {
    for (const { isIntersecting, target } of entries) {
        const state = target.getAttribute("data-state");
        if (isIntersecting && state === "ignored")                                          { updateRowAttribute(target, "data-state", "download-pending"); }
        if (!isIntersecting && !["upload-pending", "uploading", "ignored"].includes(state)) { updateRowAttribute(target, "data-state", "ignored"); }
    }
}, { rootMargin: `${window.innerHeight / 2}px 0px ${window.innerHeight}px 0px` });
(async () => observeOnce('.neo-library--row[data-img-url]', row => fillRowsObserver.observe(row), { domRoot: await getLibraryTable() }))();

export async function updateRowAttribute(rowNode, key, value) {
    const previousImgUrl = rowNode.getAttribute("data-img-url"); const previousThumbnailImgUrl = rowNode.getAttribute("data-thumbnail-img-url");

    const currentState = rowNode.getAttribute("data-state");
    if (key === "data-state") {
        switch (currentState) {
            case "ignored":          if (!["ignored", "download-pending", "upload-pending"].includes(value)) { return; } break;
            case "upload-pending":   if (!["ignored", "uploading"].includes(value))                          { throw new Error(`Unexpected state change! ${currentState} -> ${value}`); } break;
            case "uploading":        if (!["ignored", "download-pending"].includes(value))                   { throw new Error(`Unexpected state change! ${currentState} -> ${value}`); } break;
            case "download-pending": if (!["ignored", "downloading", "download-pending"].includes(value))    { throw new Error(`Unexpected state change! ${currentState} -> ${value}`); } break;
            case "downloading":      if (!["ignored", "downloaded", "download-pending"].includes(value))     { throw new Error(`Unexpected state change! ${currentState} -> ${value}`); } break;
            case "downloaded":       if (!["ignored", "download-pending"].includes(value))                   { throw new Error(`Unexpected state change! ${currentState} -> ${value}`); } break;
        }
    }

    if (key != null) { value == null ? rowNode.removeAttribute(key) : rowNode.setAttribute(key, value); }

    if (rowNode.getAttribute("data-state") === "ignored" && rowNode.getAttribute("data-thumbnail-img-url")?.startsWith("blob:") && rowNode.getAttribute("data-thumbnail-img-url") !== rowNode.getAttribute("data-img-url")) { rowNode.removeAttribute("data-thumbnail-img-url"); }
    const retainedBlobUrls = new Set([rowNode.getAttribute("data-img-url"), rowNode.getAttribute("data-thumbnail-img-url")]);
    const obsoleteBlobUrls = [...new Set([previousImgUrl, previousThumbnailImgUrl])].filter(previousBlobUrl => previousBlobUrl?.startsWith("blob:") && !retainedBlobUrls.has(previousBlobUrl));

    if (rowNode.getAttribute("data-state") === "ignored") { rowNode.innerHTML = ""; for (const obsoleteBlobUrl of obsoleteBlobUrls) { URL.revokeObjectURL(obsoleteBlobUrl); } return; }
    const imgUrl               =            rowNode.getAttribute("data-img-url");
    const thumbnailImgUrl      =            rowNode.getAttribute("data-thumbnail-img-url");
    const author               =            rowNode.getAttribute("data-author");
    const title                =            rowNode.getAttribute("data-title");
    const modifiedDate         =            rowNode.getAttribute("data-modified-date");
    const message              =            rowNode.getAttribute("data-message");
    const dbEntriesUsingImage  = JSON.parse(rowNode.getAttribute("data-db-entries-using-image") || "null");
    const fileSizes            = JSON.parse(rowNode.getAttribute("data-file-sizes") || 'null');
    const isNeoDraw            =            rowNode.getAttribute("data-is-neodraw") === "true";
    const pathRel              =            rowNode.getAttribute("data-filename") || imgUrl.replace(uploadsUrl() + "/", "");

    const cachebustDate = [modifiedDate, jsVar("neoLibraryImageCachebustLastChangeDate")].filter(Boolean).join("-");
    const cachebustedImgUrl          = fitProtocolToFetchImgUrl(addCacheBust(imgUrl, cachebustDate));
    const cachebustedThumbnailImgUrl = fitProtocolToFetchImgUrl(addCacheBust(thumbnailImgUrl ?? imgUrl, cachebustDate));

    const newRowTemplate = document.createElement("template");
    const fileType = getFileType(pathRel);
    updateRowAttribute.loadedImgUrls ??= [];
    newRowTemplate.innerHTML = `<div class="neo-library--row" data-state="ignored">` +

    `<div class="neo-library--column-image${fileType === "image" ? ' neo-library--checkerboard-background' : ''}${updateRowAttribute.loadedImgUrls.includes(imgUrl) ? ' neo-library--hide-skeleton' : ''}" onclick="if (event.metaKey || event.ctrlKey) { window.open('${escapeInlineJsString(cachebustedImgUrl)}', '_blank', 'noopener,noreferrer'); return; } document.querySelector('#neo-library--preview-popup').open('${escapeInlineJsString(cachebustedImgUrl)}')">
        ${fileType === "image" ?
        `<img src="${escapeHtml(cachebustedThumbnailImgUrl)}" loading="lazy" fetchpriority="low" alt="${escapeHtml(title ?? "")}" onload="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}')" onerror="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}')"
        ${
            /Chrome/.test(navigator.userAgent) && (!/Safari/.test(navigator.userAgent) || /Chrome/.test(navigator.userAgent)) ?
            `ondragstart="event.dataTransfer.setData('DownloadURL', 'image/${escapeInlineJsString(pathRel.split(".").pop())}:${escapeInlineJsString(pathRel.split("/").pop())}:${escapeInlineJsString(cachebustedImgUrl)}');"` :
            `onmouseover="if (event.target.src === '${escapeInlineJsString(cachebustedImgUrl)}') { return; } event.target.src = '${pluginUrl()}/img/_global-placeholder-load-icon.svg'; requestAnimationFrame(async () => { while (!event.target.complete) { await new Promise(requestAnimationFrame); } event.target.src = '${escapeInlineJsString(cachebustedImgUrl)}'; });"`
        }
        oncontextmenu="event.target.src = '${escapeInlineJsString(cachebustedImgUrl)}';" />
        ` :
        fileType === "video" ? `<video src="${escapeHtml(cachebustedThumbnailImgUrl)}" preload="metadata" onloadedmetadata="event.target.currentTime = 1; window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}');"                                 onerror="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}');"></video>` :
        fileType === "audio" ? `<img src="${pluginUrl()}/img/_global-placeholder-audio-icon.svg"          onload="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}')"                                                                          onerror="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}');" loading="lazy" alt="${escapeHtml(title ?? "")}">` :
        fileType === "pdf"   ? `<img src="${escapeHtml(!thumbnailImgUrl || thumbnailImgUrl === imgUrl ? `${pluginUrl()}/img/_global-placeholder-pdf-icon.svg` : thumbnailImgUrl)}" onload="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}')" onerror="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}');" loading="lazy" alt="${escapeHtml(title ?? "")}">` :
        fileType === "txt"   ? `<img src="${pluginUrl()}/img/_global-placeholder-txt-icon.svg"            onload="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}')"                                                                          onerror="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}');" loading="lazy" alt="${escapeHtml(title ?? "")}">` :
        fileType === "zip"   ? `<img src="${pluginUrl()}/img/_global-placeholder-zip-icon.svg"            onload="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}')"                                                                          onerror="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}');" loading="lazy" alt="${escapeHtml(title ?? "")}">` :
                               `<img src="${pluginUrl()}/img/_global-placeholder-file-icon.svg"           onload="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}')"                                                                          onerror="window.neoLibraryOnImageComplete('${escapeInlineJsString(imgUrl)}');" loading="lazy" alt="${escapeHtml(title ?? "")}">`
        }
    </div>` +

    `<div class="neo-library--column-info neo-library--hide-skeleton">
        <strong class="neo-library--info-title neo-library--show-${isNeoDraw ? 'neodraw' : fileType}-indicator" title="${escapeHtml(author ? neo__("by", "von") + " " + author : "")}" onclick="window.neoLibraryOnDetailsClicked('${escapeInlineJsString(imgUrl)}')">
            ${isNewNeoDrawImageRow(rowNode) && ["title", "url"].includes(getSortMode()) ? `<img class="neo-library--new-icon" src="${pluginUrl()}/img/neo-library--new-icon.svg" alt="${neo__('New', 'Neu')}">` : ""}
            ${escapeHtml(title || pathRel)}
        </strong>
        ${isNewNeoDrawImageRow(rowNode) ? `<div class="neo-library--info-new">${neo__('New neoDraw images with filename neodraw-123.svg are sorted to the top. Use the neoRename plugin for renaming.', 'Neue neoDraw-Bilder mit Dateiname neodraw-123.svg werden nach oben sortiert. Zum Umbenennen empfehlen wir das neoRename-Plugin.')}</div>` : ""}
        <div class="neo-library--info-rel-path" onclick="window.neoLibraryOnCopyButtonClicked('${escapeInlineJsString(imgUrl)}')">
            <span title="${escapeHtml(pathRel)}">${message || escapeHtml(pathRel)}</span>
            <a href="${escapeHtml(imgUrl)}" target="_blank" rel="noopener noreferrer" class="neo-library--info-link" onclick="event.preventDefault();">
                <button class="neo-library--button-copy" data-neo-library--tooltip="${neo__('Copy URL', 'URL kopieren')}"><svg class="neo-library--icon" viewBox="0 0 100 100"><use xlink:href="#_global-button-link-icon"></use></svg></button>
            </a>
        </div>
    </div>` +

    `<div class="neo-library--column-buttons neo-library--hide-skeleton">
        ${fileType === "image" ? `<button class="neo-library--button-edit"      onclick="window.neoLibraryOnEditButtonClicked('${escapeInlineJsString(imgUrl)}', event)"      data-neo-library--tooltip="${isNeoDraw ? neo__('Edit', 'Bearbeiten') : neo__('Insert into\nneoDraw', 'Einfügen in\nneoDraw')}">                                                                                                                                    <svg class="neo-library--icon" viewBox="0 0 100 100"><use xlink:href="${isNeoDraw ? '#neo-library--button-edit-icon' : '#neo-library--button-insert-icon'}">                  </svg></button>` : ""}
                                  <button class="neo-library--button-download"  onclick="window.neoLibraryOnDownloadButtonClicked('${escapeInlineJsString(imgUrl)}', event)"  data-neo-library--tooltip="${neo__('Download', 'Herunterladen')}"${isNeoDraw ? ` data-neo-library--shift-tooltip="${neo__('Download\nas PNG', 'Herunterladen\nals PNG')}" title="${neo__('Hold shift for PNG download', 'Shift halten für PNG-Download')}"` : ''}> <svg class="neo-library--icon" viewBox="0 0 100 100"><use xlink:href="#neo-library--button-download-icon">                                                                    </svg></button>
                                  <button class="neo-library--button-rename"    onclick="window.neoLibraryOnRenameButtonClicked('${escapeInlineJsString(imgUrl)}')"           data-neo-library--tooltip="${neo__('Rename', 'Umbenennen')}">                                                                                                                                                                                                      <svg class="neo-library--icon" viewBox="0 0 100 100"><use xlink:href="#neo-library--button-rename-icon">                                                                      </svg></button>
                                  <button class="neo-library--button-replace"   onclick="window.neoLibraryOnReplaceButtonClicked('${escapeInlineJsString(imgUrl)}')"          data-neo-library--tooltip="${neo__('Replace', 'Ersetzen')}">                                                                                                                                                                                                       <svg class="neo-library--icon" viewBox="0 0 100 100"><use xlink:href="#neo-library--button-replace-icon">                                                                     </svg></button>
                                  <button class="neo-library--button-duplicate" onclick="window.neoLibraryOnDuplicateButtonClicked('${escapeInlineJsString(imgUrl)}')"        data-neo-library--tooltip="${neo__('Duplicate', 'Duplizieren')}">                                                                                                                                                                                                  <svg class="neo-library--icon" viewBox="0 0 100 100"><use xlink:href="#neo-library--button-duplicate-icon">                                                                   </svg></button>
                                  <button class="neo-library--button-delete"    onclick="window.neoLibraryOnDeleteButtonClicked('${escapeInlineJsString(imgUrl)}', event)"    data-neo-library--tooltip="${neo__('Delete', 'Löschen')}" data-neo-library--shift-tooltip="${neo__('Delete\nimmediately', 'Lösche\nsofort')}" title="${neo__('Hold shift to delete immediately', 'Shift halten zum sofortigen Löschen')}">                         <svg class="neo-library--icon" viewBox="0 0 100 100"><use xlink:href="#neo-library--button-delete-icon">                                                                      </svg></button>
    </div>` +

    `<div class="neo-library--column-usage${dbEntriesUsingImage !== null ? ' neo-library--hide-skeleton' : '' }">` +
        (dbEntriesUsingImage == null ? "" : (
            dbEntriesUsingImage === false ? `<i>${neo__("Timeout when searching in database", "Timeout beim Suchen in Datenbank")}</i>` : (
                (dbEntriesUsingImage.length === 0 ? `<i>${neo__("Not used", "Nicht verwendet")}</i>` : "") +
                (dbEntriesUsingImage.filter((entry, index, self) => self.findIndex(e => e.postUrl === entry.postUrl) === index).map(entry => {
                    if (!entry.postUrl) { return ""; }

                    let postDisplayName = entry.postUrlDisplayPath;
                    if (postDisplayName.startsWith("?p=")) { postDisplayName = neo__("Post ", "Beitrag ") + postDisplayName.slice(3); }
                    if (postDisplayName.endsWith("/")) { postDisplayName = postDisplayName.slice(0, -1); }
                    if (postDisplayName === "") { postDisplayName = neo__("/ (home)", "/ (Startseite)"); }

                    return `<a href="${escapeHtml(entry.postUrl)}" title="${escapeHtml(entry.postTitle)}" target="_blank" rel="noopener noreferrer">${escapeHtml(postDisplayName)}</a>`;
                }).join("")) +
                (dbEntriesUsingImage.filter(entry => !entry.postUrl).length === 0 ? "" :
                    `<button class="neo-library--found-elsewhere-popup-button" onclick="window.neoLibraryOnFoundElsewhereClicked('${escapeInlineJsString(imgUrl)}')">${neo__('Found elsewhere', 'Woanders gefunden')}</button>`
                )
            )
        )) +
        (
            !isNeoDraw ? "" :
            !(fileSizes?.min && fileSizes?.max && Array.isArray(dbEntriesUsingImage) && dbEntriesUsingImage.some(entry => !!entry.postUrl)) ? "" :
            `<button class="neo-library--usage-recreate-button" onclick="window.neoLibraryRecreateNeoDrawCacheForImage('${escapeInlineJsString(imgUrl)}', true)">${neo__('Visit post to update cache', 'Post besuchen zum Aktualisieren des Cache')}</button>`
        ) +
    `</div>` +

    `<div class="neo-library--column-size${fileSizes ? ' neo-library--hide-skeleton' : ''}">` +
        (fileSizes === null ? "" : (
            (!fileSizes.raw ? "" : `<div title="${isNeoDraw ? neo__('Raw image size without neoDraw cache', 'Rohe Bildgröße ohne neoDraw Cache') : ''}">
                <span title="${neo__('Original', 'Original')}">${neo__('Orig:', 'Orig:')}</span>
                <span><a href="${fileSizes.raw.url}" target="_blank" title="${neo__("Size of original image", "Größe des Originalbildes")}">
                    ${formatKb(fileSizes.raw.size)}
                </a></span>
            </div>`) +

            `<div>
                <span title="${neo__('Optimized by neoOptimize', 'Optimiert mit neoOptimize')}">${neo__("Opti:", "Opti:")}</span>
                <span>` +
                    (
                        !(fileSizes.min && fileSizes.max) ?
                        neo__("none", "nein") :

                        `<a href="${fileSizes.min.url}" target="_blank" title="${neo__("Size of smallest cached image by neoOptimize",  "Größe des kleinsten gecachten Bildes durch neoOptimize") + (fileSizes.min?.sizeGzip ? neo__(` (${formatKb(fileSizes.min.sizeGzip)} with gzip)`, ` (${formatKb(fileSizes.min.sizeGzip)} mit gzip)`) : "") }">
                            ${formatKb(fileSizes.min.size)}
                        </a>`
                    ) +
                `</span>` +
            `</div>` +

            (
                !(fileSizes.min && fileSizes.max) || fileSizes.max.size === fileSizes.min.size ?
                "" :
                `<div>
                    <span>${neo__("to", "bis")}</span>
                    <span>
                        <a href="${fileSizes.max.url}" target="_blank" title="${neo__("Size of largest cached image",  "Größe des größten gecachten Bildes") + (fileSizes.max?.sizeGzip ? neo__(` (${formatKb(fileSizes.max.sizeGzip)} with gzip)`, ` (${formatKb(fileSizes.max.sizeGzip)} mit gzip)`) : "") }">
                            ${formatKb(fileSizes.max.size)}
                        </a>
                    </span>
                </div>`
            )
        )) +
    `</div>` +
`</div>`;
    const newRowNode = newRowTemplate.content.firstElementChild;

    window.neoLibraryOnImageComplete ??= async (imgUrl) => {
        window.neoLibraryOnImageComplete.loadingImages ??= []; if (window.neoLibraryOnImageComplete.loadingImages.includes(imgUrl)) { return; } else { window.neoLibraryOnImageComplete.loadingImages.push(imgUrl); }
        const rowNode = await getRowNode(imgUrl);
        if (!rowNode) { throw new Error("neoLibraryOnImageComplete: Row node not found for image URL: " + imgUrl); }
        if (updateRowAttribute.loadedImgUrls.includes(imgUrl)) { updateRowAttribute(rowNode, null, null); return; }

        const imgOrVideoNode = rowNode.querySelector(".neo-library--column-image>:is(img, video)");
        while (true) {
            if (rowNode.getAttribute("data-state") === "ignored") { window.neoLibraryOnImageComplete.loadingImages = window.neoLibraryOnImageComplete.loadingImages.filter(url => url !== imgUrl); return; }
                 if (imgOrVideoNode.tagName === "IMG"   && imgOrVideoNode.complete)        { break; }
            else if (imgOrVideoNode.tagName === "VIDEO" && imgOrVideoNode.readyState >= 1) { break; }
            await new Promise(requestAnimationFrame);
        }

        updateRowAttribute.loadedImgUrls.push(imgUrl);
        window.neoLibraryOnImageComplete.loadingImages = window.neoLibraryOnImageComplete.loadingImages.filter(url => url !== imgUrl);
        updateRowAttribute(rowNode, null, null);
    };

    const updateNodeDFS = (originalNode, newNode) => {
        for (const [index, newChild] of [...newNode.childNodes].entries()) {
            const existingChild = originalNode.childNodes[index];

            if (!existingChild) {
                originalNode.appendChild(newChild.cloneNode(true));
            } else {
                if (existingChild.tagName !== newChild.tagName) {
                    existingChild.replaceWith(newChild.cloneNode(true));
                } else if (existingChild.nodeType === Node.TEXT_NODE && newChild.nodeType === Node.TEXT_NODE) {
                    if (existingChild.nodeValue !== newChild.nodeValue) { existingChild.nodeValue = newChild.nodeValue; }
                } else if (existingChild.nodeType === Node.ELEMENT_NODE && newChild.nodeType === Node.ELEMENT_NODE) {
                    updateNodeDFS(existingChild, newChild);

                    for (const { name } of [...existingChild.attributes, ...newChild.attributes]) {
                        if (existingChild.getAttribute(name) !== newChild.getAttribute(name)) {
                            if (newChild.getAttribute(name) === null) { existingChild.removeAttribute(name); }
                            else { existingChild.setAttribute(name, newChild.getAttribute(name)); }
                        }
                    }
                } else {
                    neoWarn("updateNodeDFS: Unknown node type", existingChild, newChild);
                }
            }
        }

        while (originalNode.childNodes.length > newNode.childNodes.length) { originalNode.removeChild(originalNode.lastChild); }
    };
    updateNodeDFS(rowNode, newRowNode);
    for (const obsoleteBlobUrl of obsoleteBlobUrls) { URL.revokeObjectURL(obsoleteBlobUrl); }

    if (rowNode.getAttribute("data-is-neodraw") == null && rowNode.getAttribute("data-state") !== "ignored") {
        if (!imgUrl.toLowerCase().endsWith(".svg")) { updateRowAttribute(rowNode, "data-is-neodraw", "false"); }
        else {
            updateRowAttribute(rowNode, "data-is-neodraw", "pending");
            const imgNode = rowNode.querySelector(".neo-library--column-image>img");
            while (!imgNode.complete) {
                if (rowNode.getAttribute("data-state") === "ignored") { updateRowAttribute(rowNode, "data-is-neodraw", null); return; }
                await new Promise(requestAnimationFrame);
            }
            const neoDrawCheckAbortController = new AbortController(); const neoDrawCheckTimeout = setTimeout(() => neoDrawCheckAbortController.abort(), 15000);
            try {
                const svgContent = await fetch(cachebustedThumbnailImgUrl, { signal: neoDrawCheckAbortController.signal }).then(resp => resp.text());
                if (rowNode.getAttribute("data-state") === "ignored") { updateRowAttribute(rowNode, "data-is-neodraw", null); return; }
                updateRowAttribute(rowNode, "data-is-neodraw", "" + isNeoDrawImage(svgContent));
            } catch (err) { neoWarn("Could not check if image is neoDraw image", err); updateRowAttribute(rowNode, "data-is-neodraw", "false"); }
            finally { clearTimeout(neoDrawCheckTimeout); }
        }
    }

    if (key === "data-state" && currentState !== "download-pending" && value === "download-pending") {
        downloadUsageAndSizeAndTitleAndPostId();
        const rowImgUrl = rowNode.getAttribute("data-img-url"); const rowThumbnailImgUrl = rowNode.getAttribute("data-thumbnail-img-url");
        if (rowImgUrl.endsWith(".pdf") && (!rowThumbnailImgUrl || rowThumbnailImgUrl === rowImgUrl)) { createPdfThumbnailPreview(rowImgUrl); }
    }
}

async function downloadUsageAndSizeAndTitleAndPostId() {
    downloadUsageAndSizeAndTitleAndPostId.alreadyStarted ??= false;
    downloadUsageAndSizeAndTitleAndPostId.downloadLimit ??= 1;

    if (downloadUsageAndSizeAndTitleAndPostId.alreadyStarted) { return; } else { downloadUsageAndSizeAndTitleAndPostId.alreadyStarted = true; }

    while (true) {
        const downloadingRows = [...document.querySelectorAll('.neo-library--row[data-state="download-pending"]')].splice(0, downloadUsageAndSizeAndTitleAndPostId.downloadLimit);
        if (downloadingRows.length === 0) { break; }
        downloadUsageAndSizeAndTitleAndPostId.downloadLimit *= 2;
        downloadUsageAndSizeAndTitleAndPostId.downloadLimit = Math.min(64, downloadUsageAndSizeAndTitleAndPostId.downloadLimit);

        for (const row of downloadingRows) { updateRowAttribute(row, "data-state", "downloading"); }
        const imgUrls = downloadingRows.map(row => row.getAttribute("data-img-url"));

        let resp;
        try {
            resp = await fetchEndpoint("/wp-json/neo/library-get-info", {  method: "POST", body: { "img-urls": imgUrls } });
        } catch (err) {
            neoError("Network error", err);
            for (const row of downloadingRows) { updateRowAttribute(row, "data-state", "download-pending") }
            break;
        }
        let imgInfos;
        try {
            imgInfos = await extractJson(resp);
        } catch (err) {
            neoError("Server error", err);
            for (const row of downloadingRows) { updateRowAttribute(row, "data-state", "download-pending") }
            break;
        }

        for (const row of downloadingRows.filter(row => row.getAttribute("data-state") === "downloading")) {
            const imgInfo = imgInfos.find(imgInfo => imgInfo.imgUrl === row.getAttribute("data-img-url"));
            if (!imgInfo) { continue; }

            updateRowAttribute(row, "data-title",                  imgInfo.title);
            updateRowAttribute(row, "data-post-id",                imgInfo.postId);
            updateRowAttribute(row, "data-db-entries-using-image", JSON.stringify(imgInfo.dbEntriesUsingImage));
            updateRowAttribute(row, "data-file-sizes",             JSON.stringify(imgInfo.fileSizes));
            updateRowAttribute(row, "data-state", "downloaded");
        }

        await new Promise(resolve => setTimeout(resolve, 100));
    }
    downloadUsageAndSizeAndTitleAndPostId.alreadyStarted = false;
}

for (const buttonImgSrc of ["/img/neo-library--button-edit-icon.svg", "/img/neo-library--button-insert-icon.svg", "/img/neo-library--button-download-icon.svg", "/img/neo-library--button-rename-icon.svg", "/img/neo-library--button-replace-icon.svg", "/img/neo-library--button-duplicate-icon.svg", "/img/neo-library--button-delete-icon.svg", "/img/_global-button-link-icon.svg"]) {
    (async () => {
        let buttonImgSvgContent = await fetch(`${pluginUrl()}${buttonImgSrc}`).then(resp => resp.text());

        for (const nodeIdMatch of buttonImgSvgContent.matchAll(/id="([^"]+)"/g)) {
            buttonImgSvgContent = buttonImgSvgContent.replace(new RegExp(`id="${nodeIdMatch[1]}"`, "g"), `id="${buttonImgSrc.replace("/img/", "").replace(/\.svg$/, "")}-${nodeIdMatch[1]}"`);
            buttonImgSvgContent = buttonImgSvgContent.replace(new RegExp(`#${nodeIdMatch[1]}`,     "g"),    `#${buttonImgSrc.replace("/img/", "").replace(/\.svg$/, "")}-${nodeIdMatch[1]}`);
        }

        const buttonImgSvgNode = new DomNodeHelper(buttonImgSvgContent.slice(buttonImgSvgContent.indexOf("<svg"))).getNode();
        const symbolNode = document.createElementNS("http://www.w3.org/2000/svg", "symbol");
        symbolNode.setAttribute("id", buttonImgSrc.replace("/img/", "").replace(/\.svg$/, ""));
        symbolNode.innerHTML = buttonImgSvgNode.innerHTML; buttonImgSvgNode.innerHTML = symbolNode.outerHTML;

        buttonImgSvgNode.style.position = "fixed"; buttonImgSvgNode.style.top = "-100vh"; buttonImgSvgNode.style.left = "-100vw"; buttonImgSvgNode.style.transform = "scale(0)";
        const libraryTableNode = await getLibraryTable(); libraryTableNode.parentNode.insertBefore(buttonImgSvgNode, libraryTableNode);
    })();
}

async function createPdfThumbnailPreview(pdfUrl) {
    await new Promise(resolve => setTimeout(resolve, 100));
    const initialRowNode = await getRowNode(pdfUrl); if (initialRowNode.getAttribute("data-state") === "ignored") { return; }
    try {
        const pdfPreviewFile = await pdfFirstPageToWebpFile(pdfUrl, { fileName: "neo-library-pdf-preview.webp", maxSize: 900, targetBytes: 400 * 1024, shouldAbort: () => initialRowNode.getAttribute("data-state") === "ignored" });
        const rowNode = await getRowNode(pdfUrl); if (rowNode.getAttribute("data-state") === "ignored") { return; }
        updateRowAttribute(rowNode, "data-thumbnail-img-url", URL.createObjectURL(pdfPreviewFile));
    } catch (error) { if (error?.name !== "AbortError") { neoWarn("Could not create PDF thumbnail preview:", error); } }
}
