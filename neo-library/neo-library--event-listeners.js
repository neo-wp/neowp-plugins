import { neo__ } from "./_global--translation.js";
import { getLibraryTable, getRowNode, addRow, updateImageCount } from "./neo-library--globals.js";
import { updateRowAttribute } from "./neo-library--render.js";
import { animateRowShine } from "./neo-library--animations.js";
import { addCacheBust, addQueryParams, fitProtocolToFetchImgUrl, getQueryParam, removeQueryParam } from "./_global--url-helper.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";

import { DomNodeHelper } from "./_global--dom-node-helper.js";

import { neoLoadInterfaceFunc } from "./_global--interface.js";

import { jsVar } from "./_global--enqueue-loader.js";
import { requireNeoPluginForLibraryAction } from "./neo-library--plugin-requirement.js";
import { imageUsageLookupPageUrl } from "./_global-db-entries-usage-page.js";
import { observeOnce } from "./_global--observer.js";

function getDialogPopup(className, content) {
    const dialogNode = new DomNodeHelper(`
        <div class="neo-library--dialog ${className}">
            <div class="neo-library--dialog-box ${className}-box">
                ${content}
                <button class="neo-library--close-button"><img src="${pluginUrl()}/img/_global-close-icon.svg" alt="${neo__('Close', 'Schließen')}" /></button>
            </div>
        </div>
    `).getNode();
    document.body.appendChild(dialogNode);

    const closeDialog = () => { dialogNode.remove(); window.removeEventListener("keydown", closeDialogOnEsc); };
    const closeDialogOnEsc = (evt) => { if (evt.key === "Escape") { closeDialog(); } };
    dialogNode.querySelector(".neo-library--close-button").addEventListener("click", () => closeDialog());
    dialogNode.addEventListener("click", (evt) => { if (evt.target === dialogNode) { closeDialog(); } });
    window.addEventListener("keydown", closeDialogOnEsc);

    return { dialogNode, closeDialog };
}

document.querySelector("#neo-library--feedback-button")?.addEventListener("click", async () => { await (await neoLoadInterfaceFunc("neo-library", "neo-feedback.js", "interfaceOpenFeedbackDialog20260802"))(); });

window.neoLibraryOnCopyButtonClicked = async (imgUrl) => {
    await navigator.clipboard.writeText(imgUrl);
    const copyButton = (await getRowNode(imgUrl)).querySelector(".neo-library--button-copy");
    const oldTooltip = copyButton.getAttribute("data-neo-library--tooltip");
    copyButton.setAttribute("data-neo-library--tooltip", neo__("URL copied", "URL kopiert"));
    setTimeout(() => copyButton.setAttribute("data-neo-library--tooltip", oldTooltip), 1000);
};

window.neoLibraryOnDetailsClicked = async (imgUrl) => {
    const rowNode = await getRowNode(imgUrl);
    const postId = rowNode.getAttribute("data-post-id");
    if (!postId) { return alert(neo__('Could not find post ID for this image', 'Konnte keine Beitrag-ID für dieses Bild finden')); }
    const detailsUrl = addQueryParams(jsVar("neoLibraryPostDetailsUrl"), { post: postId, action: "edit" });
    const { dialogNode } = getDialogPopup("neo-library--details-dialog", `<iframe src="${detailsUrl}" title="${neo__('Details', 'Details')}"></iframe>` + `<div class="neo-library--dialog-loading-spinner"></div>`);
    const iframeNode = dialogNode.querySelector("iframe");
    iframeNode.addEventListener("load", async () => {
        dialogNode.querySelector(".neo-library--dialog-loading-spinner")?.remove();

        iframeNode.contentDocument.querySelector("#wpadminbar")?.remove();
        iframeNode.contentDocument.querySelector("html.wp-toolbar")?.style?.setProperty("padding-top", "0");
        iframeNode.contentDocument.querySelector("#adminmenumain")?.remove();
        iframeNode.contentDocument.querySelector("#wpcontent")?.style?.setProperty("margin-left", "0");
        iframeNode.contentDocument.querySelector("#wpfooter")?.remove();
        iframeNode.contentDocument.querySelectorAll(".notice:not(.notice-error)").forEach(notice => notice.remove());
        iframeNode.contentDocument.querySelectorAll(".page-title-action").forEach(notice => notice.remove());

        iframeNode.contentDocument.documentElement.style.overscrollBehavior = "contain";

        iframeNode.contentDocument.querySelector(".wp_attachment_holder")?.after(iframeNode.contentDocument.querySelector("#postbox-container-1"));
        iframeNode.contentDocument.querySelector("#postbox-container-1 .postbox-header")?.style?.setProperty("display", "none");
        iframeNode.contentDocument.querySelector("#postbox-container-1")?.style?.setProperty("margin-top", "12px");
        iframeNode.contentDocument.querySelector(".wp-heading-inline")?.style?.setProperty("display", "none");
        iframeNode.contentDocument.querySelector("#titlewrap>input")?.setAttribute("disabled", "true");
        iframeNode.contentDocument.querySelector("#titlewrap>input")?.setAttribute("style", "color: black; background: none; border: none; padding: 0; box-shadow: none;");
        iframeNode.contentDocument.querySelector("#edit-slug-box")?.style?.setProperty("display", "none");
        iframeNode.contentDocument.querySelector(".misc-pub-attachment")?.style?.setProperty("display", "none");
        iframeNode.contentDocument.querySelector(".misc-pub-download")?.style?.setProperty("display", "none");
        iframeNode.contentDocument.querySelector("#delete-action")?.style?.setProperty("display", "none");
        iframeNode.contentDocument.querySelector(".misc-pub-uploadedby>a")?.setAttribute("target", "_blank");
        iframeNode.contentDocument.querySelectorAll(".fs-notice")      .forEach(el => el.style.setProperty("display", "none", "important"));

        const uploadDateDataNode = iframeNode.contentDocument.querySelector(".misc-pub--neo-library--id");
        const uploadDateTextNode = iframeNode.contentDocument.querySelector(".misc-pub-curtime #timestamp");
        if (uploadDateDataNode && uploadDateTextNode) {
            await updateRowAttribute(rowNode, "data-upload-date", uploadDateDataNode.getAttribute("data-upload-date-gmt"));
            uploadDateTextNode.closest(".misc-pub-curtime").setAttribute("style", "display: flex; align-items: center;"); uploadDateTextNode.style.setProperty("display", "inline-flex", "important"); uploadDateTextNode.style.setProperty("align-items", "center");
            const editUploadDateButton = new DomNodeHelper(`<button type="button" class="button button-small" style="margin-left: 6px;">${neo__("Edit", "Bearbeiten")}</button>`).getNode();
            uploadDateTextNode.after(editUploadDateButton);
            editUploadDateButton.addEventListener("click", () => {
                const [uploadDate, uploadTime] = uploadDateDataNode.getAttribute("data-upload-date-local").split(" ");
                const [uploadYear, uploadMonth, uploadDay] = uploadDate.split("-"); const [uploadHour, uploadMinute, uploadSecond] = uploadTime.split(":");
                uploadDateTextNode.innerHTML = `<input type="datetime-local" value="${uploadDate}T${uploadTime}" step="1" required aria-label="${neo__("Upload date", "Uploaddatum")}"><input type="hidden" name="edit_date" value="1"><input type="hidden" name="aa" value="${uploadYear}"><input type="hidden" name="mm" value="${uploadMonth}"><input type="hidden" name="jj" value="${uploadDay}"><input type="hidden" name="hh" value="${uploadHour}"><input type="hidden" name="mn" value="${uploadMinute}"><input type="hidden" name="ss" value="${uploadSecond}">`;
                uploadDateTextNode.querySelector('input[type="datetime-local"]').addEventListener("input", event => { if (!event.target.value) { return; } const [date, time] = event.target.value.split("T"); const [year, month, day] = date.split("-"); const [hour, minute, second = "00"] = time.split(":"); uploadDateTextNode.querySelector('input[name="aa"]').value = year; uploadDateTextNode.querySelector('input[name="mm"]').value = month; uploadDateTextNode.querySelector('input[name="jj"]').value = day; uploadDateTextNode.querySelector('input[name="hh"]').value = hour; uploadDateTextNode.querySelector('input[name="mn"]').value = minute; uploadDateTextNode.querySelector('input[name="ss"]').value = second; });
                editUploadDateButton.remove();
            });
        }

        iframeNode.contentDocument.querySelector("#publish")?.setAttribute("style", "position: fixed; top: 16px; right: 20px;");
        iframeNode.contentDocument.querySelector("#major-publishing-actions")?.setAttribute("style", "height: 0; padding: 0; border: none;");
        iframeNode.contentDocument.querySelector("#contextual-help-link")?.setAttribute("style", "visibility: hidden; pointer-events: none;");
        iframeNode.contentDocument.querySelector("#contextual-help-link") ? iframeNode.contentDocument.querySelector("#contextual-help-link").innerText = iframeNode.contentDocument.querySelector("#publish")?.value ?? "Update" : null;

        iframeNode.contentDocument.querySelector(".thumbnail")?.style.setProperty("max-height", "50vh");

        iframeNode.classList.add("neo-library--iframe-loaded");
    });
};

window.neoLibraryOnEditButtonClicked = async (imgUrl, event) => {
    if (event?.metaKey || event?.ctrlKey) { window.open(addQueryParams(jsVar("neoLibraryMediaLibraryUrl"), { page: "neo-library", "neo-library--open-neodraw-img-url": imgUrl }), "_blank", "noopener,noreferrer"); return; }
    if (!(await requireNeoPluginForLibraryAction("draw"))) { return; }
    const rowNode = await getRowNode(imgUrl);
    const isNeoDraw = rowNode.getAttribute("data-is-neodraw") === "true";
    let newImgUrl = null;
    new (await neoLoadInterfaceFunc("neo-library", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))()
        .imgUrl(imgUrl)
        .on("save", ({ imgUrl }) => { newImgUrl = imgUrl; })
        .on("close", async () => {
            if (!newImgUrl) { return; }
            if (isNeoDraw) {
                if (imgUrl !== newImgUrl) {
                    const newRow = await addRow();
                    updateRowAttribute(newRow, "data-img-url", newImgUrl);
                    updateRowAttribute(newRow, "data-state", "download-pending");
                    window.scrollTo({ top: 0, behavior: "smooth" });
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    animateRowShine(newRow);
                    return;
                }
                updateRowAttribute(rowNode, "data-modified-date", new Date().toISOString());
                updateRowAttribute(rowNode, "data-thumbnail-img-url", newImgUrl);
                window.neoLibraryRecreateNeoDrawCacheForImage(newImgUrl);
            } else {
                const newRow = await addRow();
                updateRowAttribute(newRow, "data-img-url", newImgUrl);
                updateRowAttribute(newRow, "data-state", "download-pending");
                window.scrollTo({ top: 0, behavior: 'smooth' });
                await new Promise(resolve => setTimeout(resolve, 1000));
                animateRowShine(newRow);
            }
        })
        .open();
};

const neoDrawImgUrlToOpen = getQueryParam(location.href, "neo-library--open-neodraw-img-url");
if (neoDrawImgUrlToOpen) {
    customElements.whenDefined("neo-select-neo-library").then(async () => {
        await new Promise(requestAnimationFrame);
        const neoDrawRowToOpen = await getRowNode(neoDrawImgUrlToOpen);
        if (!neoDrawRowToOpen) { return; }
        neoDrawRowToOpen.scrollIntoView({ behavior: "auto", block: "center" });
        (await observeOnce(".neo-library--button-edit", undefined, { domRoot: neoDrawRowToOpen })).click();
        history.replaceState({}, "", removeQueryParam(location.href, "neo-library--open-neodraw-img-url"));
    });
}

window.neoLibraryOnDownloadButtonClicked = async (imgUrl, event) => {
    const rowNode = await getRowNode(imgUrl);
    const downloadButton = rowNode.querySelector(".neo-library--button-download");
    downloadButton.classList.add("neo-library--button-loading");
    const isNeoDraw = rowNode.getAttribute("data-is-neodraw") === "true";
    const modifiedDate = rowNode.getAttribute("data-modified-date");
    let cachebustedImgUrlOrPngBase64 = fitProtocolToFetchImgUrl(addCacheBust(imgUrl, modifiedDate));
    let filenameForDownload = imgUrl.split("/").pop();

    if (event.shiftKey && isNeoDraw) {
        if (!(await requireNeoPluginForLibraryAction("draw"))) { downloadButton.classList.remove("neo-library--button-loading"); return; }

        const editorDialog = new (await neoLoadInterfaceFunc("neo-library", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))();
        editorDialog.imgUrl(imgUrl).hidden().open();
        editorDialog.on("error", () => alert(neo__('Could not export image to PNG', 'Konnte Bild nicht als PNG exportieren')));
        await new Promise(resolve => { editorDialog.on("load", resolve); });
        const { blob } = await editorDialog.exportPngOrJpegOrWebp("image/png");

        cachebustedImgUrlOrPngBase64 = await new Promise((resolve, reject) => {
            const reader = new FileReader(); reader.readAsDataURL(blob);
            reader.onloadend = () => resolve(reader.result); reader.onerror = reject;
        });
        filenameForDownload += ".png";
    }

    const a = document.createElement("a");
    a.href = cachebustedImgUrlOrPngBase64; a.download = filenameForDownload;
    a.click();

    await new Promise(resolve => setTimeout(resolve, 1000));
    downloadButton.classList.remove("neo-library--button-loading");
};

export async function onRenameCompleted({ oldImgUrl, oldTitle, oldPathRel, oldSlug, newImgUrl, newTitle, newPathRel, newSlug }) {
    const updatedRowNode = await getRowNode(oldImgUrl);
    if (!updatedRowNode) { return; }
    const rect = updatedRowNode.getBoundingClientRect(); if (!(rect.top >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight))) { updatedRowNode.scrollIntoView({ behavior: "smooth", block: "start" }); }
    await updateRowAttribute(updatedRowNode, "data-title", newTitle);
    await updateRowAttribute(updatedRowNode, "data-img-url", newImgUrl);
    await updateRowAttribute(updatedRowNode, "data-thumbnail-img-url", newImgUrl);
    await updateRowAttribute(updatedRowNode, "data-modified-date", new Date(new Date(updatedRowNode.getAttribute("data-modified-date")).getTime() + 1000).toISOString().replace('T', ' ').substring(0, 19));
    await updateRowAttribute(updatedRowNode, "data-db-entries-using-image", null);
    await updateRowAttribute(updatedRowNode, "data-state", "download-pending");
    animateRowShine(updatedRowNode);
}
window.neoLibraryOnRenameButtonClicked = async (imgUrl) => {
    if (!(await requireNeoPluginForLibraryAction("rename"))) { return; }
    const navigationImgUrls = [...(await getLibraryTable()).querySelectorAll(".neo-library--row[data-img-url]:not(.neo-library--row-search-hidden):not(.neo-library--deleting-animation)")].map(row => row.getAttribute("data-img-url"));
    await (await neoLoadInterfaceFunc("neo-library", "neo-rename--dialog.js", "interfaceOpenRenameDialog20250813"))({ filterInputText: imgUrl, inputMode: "single", onUpdateCallback: onRenameCompleted, navigationImgUrls });
};

async function onReplaceCompleted({ oldImgUrl, newImgUrl, result }) {
    const updatedRowNode = await getRowNode(oldImgUrl);
    if (!updatedRowNode) { return; }
    const rect = updatedRowNode.getBoundingClientRect(); if (!(rect.top >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight))) { updatedRowNode.scrollIntoView({ behavior: "smooth", block: "start" }); }
    updateRowAttribute(updatedRowNode, "data-title", result.title);
    updateRowAttribute(updatedRowNode, "data-img-url", newImgUrl);
    updateRowAttribute(updatedRowNode, "data-thumbnail-img-url", result.thumbnailUrl || newImgUrl);
    updateRowAttribute(updatedRowNode, "data-upload-date", result.uploadDate);
    updateRowAttribute(updatedRowNode, "data-modified-date", result.modifiedDate || new Date().toISOString());
    updateRowAttribute(updatedRowNode, "data-is-neodraw", null);
    updateRowAttribute(updatedRowNode, "data-db-entries-using-image", null);
    updateRowAttribute(updatedRowNode, "data-file-sizes", null);
    updateRowAttribute(updatedRowNode, "data-state", "download-pending");
    animateRowShine(updatedRowNode);
}
window.neoLibraryOnReplaceButtonClicked = async (imgUrl) => {
    if (!(await requireNeoPluginForLibraryAction("replace"))) { return; }
    await (await neoLoadInterfaceFunc("neo-library", "neo-replace--dialog.js", "interfaceOpenReplaceDialog20260525"))({ imgUrl, onUpdateCallback: async ({ oldImgUrl, newImgUrl, result }) => await onReplaceCompleted({ oldImgUrl, newImgUrl, result }) });
};

window.neoLibraryOnDuplicateButtonClicked = async (imgUrl) => {
    const rowNode = await getRowNode(imgUrl);
    const duplicateButton = rowNode.querySelector(".neo-library--button-duplicate");
    duplicateButton.classList.add("neo-library--button-loading");
    if (!(await requireNeoPluginForLibraryAction("duplicate"))) { duplicateButton.classList.remove("neo-library--button-loading"); return; }
    let newImgUrl;
    try {
        newImgUrl = await (await neoLoadInterfaceFunc("neo-library", "neo-duplicate.js", "interfaceDuplicate20250302"))(imgUrl);
    } catch (err) {
        alert(err.message);
        duplicateButton.classList.remove("neo-library--button-loading");
        return;
    }
    const duplicatedRow = await addRow();
    updateRowAttribute(duplicatedRow, "data-img-url",    newImgUrl);
    updateRowAttribute(duplicatedRow, "data-is-neodraw", rowNode.getAttribute("data-is-neodraw"));
    updateRowAttribute(duplicatedRow, "data-title",      rowNode.getAttribute("data-title"));
    duplicateButton.classList.remove("neo-library--button-loading");
    updateRowAttribute(duplicatedRow, "data-state", "download-pending");
    window.scrollTo({ top: 0, behavior: 'smooth' });
    await new Promise(resolve => setTimeout(resolve, 1000));
    animateRowShine(duplicatedRow);
};

window.neoLibraryOnDeleteButtonClicked = async (imgUrl, event) => {
    if (!(event.shiftKey || confirm(neo__('Are you sure you want to delete this image (hold Shift to delete immediately)?', 'Soll das Bild wirklich gelöscht werden (Umschalttaste halten zum sofortigen Löschen)?')))) { return; }
    const rowNode = await getRowNode(imgUrl);
    updateRowAttribute(rowNode, "data-message", neo__("Deleting...", "Lösche..."));
    const deleteButton = rowNode.querySelector(".neo-library--button-delete");
    deleteButton.classList.add("neo-library--button-loading");
    rowNode.style.pointerEvents = "none";
    try {
        await fetchEndpoint("/wp-json/neo/library-delete", { method: "POST", body: { "img-url": imgUrl } }).then(extractJson);
        rowNode.classList.add("neo-library--deleting-animation");
        setTimeout(() => { updateRowAttribute(rowNode, "data-state", "ignored"); rowNode.remove(); }, event.shiftKey ? 200 / 2 + 0 : 200 + 200);
        updateImageCount();
    } catch (err) {
        updateRowAttribute(rowNode, "data-message", `<span><img class="neo-library--lucide-icon" src="${pluginUrl()}/_global-lucide-icons-thirdparty/circle-x.svg" alt=""></span> ` + neo__("Error deleting: ", "Fehler beim Löschen: ") + err.message);
        deleteButton.classList.remove("neo-library--button-loading");
        rowNode.style.pointerEvents = "";
        alert(err.message);
    }
};

window.neoLibraryRecreateNeoDrawCacheForImage = async (imgUrl, showErrorAlerts) => {
    const rowNode = await getRowNode(imgUrl);
    updateRowAttribute(rowNode, "data-file-sizes", "");
    rowNode.querySelector(".neo-library--usage-recreate-button")?.remove();

    try {
        const dbEntriesUsingImage = JSON.parse(rowNode.getAttribute("data-db-entries-using-image"));
        let postUrls = dbEntriesUsingImage.map(dbEntry => dbEntry.postUrl).filter(Boolean);
        postUrls = postUrls.filter((postUrl, index) => postUrls.indexOf(postUrl) === index);
        const postHtmlContents = await Promise.allSettled(postUrls.map(postUrl => fetch(addCacheBust(postUrl)).then(resp => resp.text()) ));
        const imgId = await fetchEndpoint("/wp-json/neo/library-get-id-from-url", { method: "GET", query: { "img-url": imgUrl } }).then(extractJson).then(data => data.postId);

        const imgIdQuerySearchString = (await neoLoadInterfaceFunc("neo-library", "neo-optimize.js", "interfaceGetNeoOptimizeQueryParamWithImageId20251007"))(imgId);
        if (!postHtmlContents.every(html => html.status === "fulfilled" && (html.value.includes(imgUrl) || html.value.includes(imgIdQuerySearchString)))) {
            if (showErrorAlerts) {
                const postUrlsWithoutImage = postUrls.filter((postUrl, index) => postHtmlContents[index].status !== "fulfilled" || (!postHtmlContents[index].value.includes(imgUrl) && !postHtmlContents[index].value.includes(imgIdQuerySearchString)));
                alert(neo__(`Could not find image URL in all post HTML contents. Please visit the post manually to recreate the neoCache. (Did not find the image on ${postUrlsWithoutImage.join(', ')})`, `Die Bild-URL konnte nicht in allen Post-Inhalten gefunden werden. Bitte den Beitrag manuell besuchen, um den neoCache neu zu erstellen. (Konnte Bild nicht finden auf ${postUrlsWithoutImage.join(', ')})`));
            }
            updateRowAttribute(rowNode, null, null);
        }
        updateRowAttribute(rowNode, "data-state", "download-pending");
    } catch (err) {
        if (showErrorAlerts) { alert(neo__('Could not recreate neoCache', 'Konnte neoCache nicht neu erstellen') + ': ' + err.message); }
        updateRowAttribute(rowNode, null, null);
    }
};

window.neoLibraryOnFoundElsewhereClicked = async (imgUrl) => {
    const dbUsageUrl = imageUsageLookupPageUrl({ imgUrl });
    const { dialogNode } = getDialogPopup("neo-library--db-usage-dialog", `<iframe src="${dbUsageUrl}" title="${neo__('Image in WordPress Database', 'Bild in der WordPress Datenbank')}"></iframe>` + `<div class="neo-library--dialog-loading-spinner"></div>`);
    dialogNode.querySelector("iframe").addEventListener("load", () => { dialogNode.querySelector("iframe").classList.add("neo-library--iframe-loaded"); dialogNode.querySelector(".neo-library--dialog-loading-spinner").remove(); });
};
