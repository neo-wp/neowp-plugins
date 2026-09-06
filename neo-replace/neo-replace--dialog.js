import { extractJson } from "./_global--extract-json.js";
import { fetchEndpoint, refreshNonceIfOutdated } from "./_global--endpoint.js";
import { escapeHtml } from "./_global--dom-node-helper.js";
import { observeClick, delay } from "./_global--observer.js";
import { addCacheBust } from "./_global--url-helper.js";
import { getFileType } from "./_global-media-file-type.js";

import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { pricingUrl } from "./_global-pricing-url.js";
import Swal from "./_global-sweetalert2.js";
import { neo__ } from "./_global--translation.js";
import { formatKb } from "./_global--math.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { imageUsageLookupPageUrl } from "./_global-db-entries-usage-page.js";
import { isModuleAvailable } from "./_global--interface.js";
import { isPagebuilderOpen } from "./_global--pagebuilder-warning-detect.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { cleanTitle, cleanPathRel, cleanSlug, initCleanFileNameEmojiMap } from "./_global-clean-file-name.js";

let cssLoadingPromise = Promise.resolve();
if (!document.querySelector("link[data-neo-replace--dialog-css]")) {
    const linkNode = document.createElement("link");
    linkNode.rel = "stylesheet";
    linkNode.setAttribute("data-neo-replace--dialog-css", "true");
    cssLoadingPromise = new Promise((resolve, reject) => { linkNode.onload = () => resolve(); linkNode.onerror = () => reject(); });
    linkNode.href = pluginUrl() + "/neo-replace--dialog.css";
    document.head.appendChild(linkNode);
}

function previewMarkup({ label, url = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==", fileType = "image", filename = "", fileSize = null, width = null, height = null, isEmpty = false, includeProgressbar = false, emptyMetaText = "", unknownMetaText = "-", showLabel = true, previewTabIndex = null }) {
    const iconClass = fileType === "image" && !isEmpty ? "" : " neo-replace--dialog-preview-icon";
    const dataType = fileType === "image" && !isEmpty ? "" : ` data-neo-replace--file-type="${escapeHtml(fileType)}"`;
    const previewUrlAttribute = url && !isEmpty ? ` data-neo-replace--preview-url="${escapeHtml(url)}"` : "";
    const previewTabIndexAttribute = previewTabIndex === null ? "" : ` tabindex="${String(previewTabIndex)}"`;

    const previewMetaParts = [];
    previewMetaParts.push(isEmpty ? emptyMetaText : (width && height ? `${Math.round(width)} × ${Math.round(height)}px` : (fileType === "image" ? unknownMetaText : "")));
    previewMetaParts.push(fileSize === null || fileSize === undefined ? "" : formatKb(fileSize));

    return `<button class="neo-replace--dialog-preview" type="button"${previewUrlAttribute}${previewTabIndexAttribute}>
        <span class="neo-replace--dialog-preview-media${iconClass}"${dataType}>${fileType === "image" && !isEmpty ? `<img src="${escapeHtml(addCacheBust(url))}" alt="">` : ""}</span>
        <span class="neo-replace--dialog-preview-text">
            ${showLabel ? `<span class="neo-replace--dialog-preview-label">${escapeHtml(label)}</span>` : ""}
            <span class="neo-replace--dialog-preview-filename">${escapeHtml(filename || (isEmpty ? "" : "–"))}</span>
            <span class="neo-replace--dialog-preview-meta"><span class="neo-replace--dialog-preview-meta-text">${escapeHtml(previewMetaParts.filter(Boolean).join(" · "))}</span>${includeProgressbar ? `<span class="neo-replace--dialog-progressbar"><span></span></span>` : ""}</span>
        </span>
    </button>`;
}

export async function openReplaceDialog({ imgUrl, onUpdateCallback = () => {} } = {}) {
    if (openReplaceDialog.isOpen) { return false; }
    if (!(typeof imgUrl === "string" && imgUrl !== "")) { throw new Error("Image URL is missing."); }
    let info = { imgUrl: imgUrl, pathRel: new URL(imgUrl, location.origin).pathname, fileSize: null, width: null, height: null, dbEntriesUsingImage: null };
    const currentFileType = getFileType(info.imgUrl);
    await cssLoadingPromise;
    if (openReplaceDialog.isOpen) { return false; }
    openReplaceDialog.isOpen = true;

    let selectedFile = null;
    let selectedObjectUrl = null;
    let selectedFileVersion = 0;
    let progress = false;
    let didReplace = false;
    const usageMarkup = (usageInfo) => {
        return `<div class="neo-replace--dialog-usage-pro">
        <p>${neo__("See where this media file is used before replacing it.", "Sieh vor dem Ersetzen, wo diese Mediendatei verwendet wird.")}</p>
        <span class="neo-replace--dialog-usage-pro-action">
            <neo-button-neo-replace href="${pricingUrl()}" target="_blank" show-pro-crown>${neo__("Get neoPro", "neoPro holen")}</neo-button-neo-replace>
        </span>
    </div>`; 
        if (!usageInfo) { return `<div class="neo-replace--dialog-usage-note">${neo__("Loading...", "Lädt...")}</div>`; }
        if (usageInfo.dbEntriesUsingImage === false) { return `<div class="neo-replace--dialog-usage-note">${neo__("Usage lookup timed out.", "Verwendungs-Suche hat zu lange gedauert.")}</div>`; }
        const usageEntryDisplayPath = (entry) => entry.postUrlDisplayPath || (entry.postUrl ? new URL(entry.postUrl, location.origin).pathname : `/${entry.tableName || "database"}/`);
        const usageEntryTitle = (entry) => entry.postTitle || entry.tableName || "Found elsewhere";
        const entries = usageInfo.dbEntriesUsingImage ?? [];
        const dbUsageUrl = imageUsageLookupPageUrl({ imgUrl: info.imgUrl });
        if (entries.length === 0) { return `<div class="neo-replace--dialog-usage-note">${neo__("No usages found.", "Keine Verwendungen gefunden.")}</div>`; }
        return `${entries.map((entry) => {
            const isOptionEntry = !entry.postUrl && entry.contentPreview?.option_name;
            if (isOptionEntry && dbUsageUrl) { return `<a class="neo-replace--dialog-usage-entry" href="${escapeHtml(dbUsageUrl)}" target="_blank" rel="noopener">${escapeHtml(`${entry.tableName || "wp_options"}.${entry.contentPreview.option_name}`)}</a>`; }
            if (isOptionEntry) { return `<span class="neo-replace--dialog-usage-entry">${escapeHtml(`${entry.tableName || "wp_options"}.${entry.contentPreview.option_name}`)}</span>`; }
            if (!entry.postUrl) { return ""; }
            const entryContentMarkup = `<span class="neo-replace--dialog-usage-path">${escapeHtml(usageEntryDisplayPath(entry))}</span>
            <span class="neo-replace--dialog-usage-separator">·</span>
            <span class="neo-replace--dialog-usage-title">${escapeHtml(usageEntryTitle(entry))}</span>`;
            return `<a class="neo-replace--dialog-usage-entry" href="${escapeHtml(entry.postUrl)}" target="_blank" rel="noopener">${entryContentMarkup}</a>`;
        }).join("")}${entries.some(entry => !entry.postUrl && !entry.contentPreview?.option_name) && dbUsageUrl ? `<a class="neo-replace--dialog-usage-entry" href="${escapeHtml(dbUsageUrl)}" target="_blank" rel="noopener">${neo__("Found elsewhere", "Woanders gefunden")}</a>` : ""}`;
    };

    document.querySelector("#neo-replace--dialog")?.remove();
    document.body.insertAdjacentHTML("beforeend", `
        <div id="neo-replace--dialog" class="neo-replace--dialog">
            <div class="neo-replace--dialog-backdrop"></div>
            <div class="neo-replace--dialog-drop-feedback">${neo__("Drop to Replace", "Zum Ersetzen ablegen")}</div>
            <div class="neo-replace--dialog-box">
                <div class="neo-replace--dialog-file-section">
                    <div class="neo-replace--dialog-file-heading-row">
                        <span>${neo__("Current file", "Aktuelle Datei")}</span>
                        <span>${neo__("New file", "Neue Datei")}</span>
                    </div>
                    <div class="neo-replace--dialog-preview-row">
                    <span class="neo-replace--dialog-current-preview">${previewMarkup({ label: neo__("Current file", "Aktuelle Datei"), url: info.imgUrl, fileType: currentFileType, filename: info.pathRel?.split("/").pop(), fileSize: info.fileSize, width: info.width, height: info.height, unknownMetaText: "", showLabel: false })}</span>
                    <div class="neo-replace--dialog-dropzone" tabindex="0" role="button">
                        <input type="file" class="neo-replace--dialog-file-input" tabindex="-1">
                        <span class="neo-replace--dialog-new-preview">${previewMarkup({ label: neo__("Select file", "Datei auswählen"), fileType: "other", isEmpty: true, includeProgressbar: true, emptyMetaText: neo__("or drag & drop", "oder per Drag & Drop"), previewTabIndex: -1 })}</span>
                    </div>
                    </div>
                    <div class="neo-replace--dialog-actions">
                        <div class="neo-replace--dialog-options">
                            <label><input type="checkbox" class="neo-replace--dialog-checkbox-filename"> ${neo__("Use uploaded filename (title, filename, slug)", "Hochgeladenen Dateinamen übernehmen (Titel, Dateiname, Slug)")}</label>
                            <label><input type="checkbox" class="neo-replace--dialog-checkbox-date"> ${neo__("Set upload date to today", "Uploaddatum auf heute setzen")}</label>
                        </div>
                        <button class="neo-replace--dialog-replace-button" type="button" disabled><span class="neo-replace--dialog-replace-button-text">${neo__("Replace", "Ersetzen")}</span><span class="neo-replace--spinner"></span></button>
                    </div>
                </div>

                <div class="neo-replace--dialog-detail-section${(() => { return " neo-replace--dialog-detail-section-pro-teaser";})()}">
                    <div class="neo-replace--dialog-usage">
                        ${(() => { return "";})()}
                        <div class="neo-replace--dialog-usage-list">${usageMarkup(null)}</div>
                    </div>
                </div>
                ${isModuleAvailable("neo-feedback") ? `<neo-info-tooltip-neo-replace class="neo-replace--dialog-feedback-button" no-click-open instant-hover><button slot="icon" type="button" aria-label="${neo__("Give feedback", "Feedback geben")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/message-square.svg" alt=""></button>${neo__("Give feedback", "Feedback geben")}</neo-info-tooltip-neo-replace>` : ""}
                ${jsVar("neoReplaceSettingsSectionUrl") ? `<neo-info-tooltip-neo-replace class="neo-replace--dialog-settings-button" no-click-open instant-hover><a slot="icon" href="${jsVar("neoReplaceSettingsSectionUrl")}" target="_blank" rel="noopener noreferrer" aria-label="${neo__("Open settings", "Einstellungen öffnen")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/settings.svg" alt=""></a>${neo__("Open settings", "Einstellungen öffnen")}</neo-info-tooltip-neo-replace>` : ""}
                <button class="neo-replace--dialog-close-button" type="button" title="${neo__("Close", "Schließen")}" aria-label="${neo__("Close", "Schließen")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/x.svg" alt=""></button>
            </div>
            <neo-media-preview-popup-neo-replace></neo-media-preview-popup-neo-replace>
        </div>
    `);

    const dialog            = document.querySelector("#neo-replace--dialog");
    const currentPreview    = dialog.querySelector(".neo-replace--dialog-current-preview");
    const fileInput         = dialog.querySelector(".neo-replace--dialog-file-input");
    const newPreview        = dialog.querySelector(".neo-replace--dialog-new-preview");
    const usageList         = dialog.querySelector(".neo-replace--dialog-usage-list");
    const dropzone          = dialog.querySelector(".neo-replace--dialog-dropzone");
    const replaceButton     = dialog.querySelector(".neo-replace--dialog-replace-button");
    const filenameCheckbox  = dialog.querySelector(".neo-replace--dialog-checkbox-filename");
    const dateCheckbox      = dialog.querySelector(".neo-replace--dialog-checkbox-date");
    const closeButton       = dialog.querySelector(".neo-replace--dialog-close-button");
    const mediaPreviewPopup = dialog.querySelector("neo-media-preview-popup-neo-replace");
    filenameCheckbox.checked = localStorage.getItem("neo-replace--dialog-use-uploaded-filename") === "true";
    dateCheckbox.checked = localStorage.getItem("neo-replace--dialog-update-upload-date") === "true";
    document.activeElement?.blur();
    dropzone.focus({ preventScroll: true });

    dialog.querySelector(".neo-replace--dialog-feedback-button")?.addEventListener("click", async () => { await (await neoLoadInterfaceFunc("neo-replace", "neo-feedback.js", "interfaceOpenFeedbackDialog20260802"))({ swalContainerClass: "neo-replace--dialog-swal" }); });

    return await new Promise((resolve) => {
        const finishDialog = () => {
            if (selectedObjectUrl) { URL.revokeObjectURL(selectedObjectUrl); }
            window.removeEventListener("keydown", onKeyPress);
            dialog.remove();
            openReplaceDialog.isOpen = false;
            resolve(didReplace);
        };
        (async () => {
            try {
                info = await extractJson(await fetchEndpoint("/wp-json/neo/replace-info", { method: "POST", body: { "img-url": imgUrl } }));
                if (window.neoReplaceMockedUsageList !== undefined) { info.dbEntriesUsingImage = window.neoReplaceMockedUsageList; }
                if (!dialog.isConnected) { return; }
                currentPreview.innerHTML = previewMarkup({ label: neo__("Current file", "Aktuelle Datei"), url: info.imgUrl, fileType: currentFileType, filename: info.pathRel?.split("/").pop(), fileSize: info.fileSize, width: info.width, height: info.height, showLabel: false });
                usageList.innerHTML = usageMarkup(info);
            } catch (error) {
                if (!dialog.isConnected) { return; }
                await Swal.fire({ icon: "error", title: neo__("Replacement failed", "Ersetzen fehlgeschlagen"), text: error.message, customClass: { container: "neo-replace--dialog-swal" } });
                if (!progress) { finishDialog(); }
            }
        })();

        const setSelectedFile = async (file) => {
            if (!file || progress) { return; }
            const fileVersion = ++selectedFileVersion;
            const fileType = getFileType(file.name);

            if (fileType !== currentFileType) {
                selectedFile = null;
                replaceButton.disabled = true;
                if (selectedObjectUrl) { URL.revokeObjectURL(selectedObjectUrl); selectedObjectUrl = null; }
                newPreview.innerHTML = previewMarkup({ label: neo__("Select file", "Datei auswählen"), fileType: "other", isEmpty: true, includeProgressbar: true, emptyMetaText: neo__("or drag & drop", "oder per Drag & Drop"), previewTabIndex: -1 });
                await Swal.fire({ icon: "error", title: neo__("Wrong media type", "Falscher Medientyp"), text: neo__("The replacement file must use the same or similar media type as the current file.", "Die Ersatzdatei muss denselben oder einen ähnlichen Medientyp wie die aktuelle Datei haben."), customClass: { container: "neo-replace--dialog-swal" } });
                if (fileVersion === selectedFileVersion) { fileInput.value = ""; }
                return;
            }

            replaceButton.disabled = true;
            selectedFile = file;
            if (selectedObjectUrl) { URL.revokeObjectURL(selectedObjectUrl); }
            selectedObjectUrl = URL.createObjectURL(file);
            let dimensions = { width: null, height: null };

            if (getFileType(file.name) === "image" && file.type !== "image/svg+xml" && !file.name.toLowerCase().endsWith(".svg")) {
                const dimensionsObjectUrl = URL.createObjectURL(file);
                try {
                    const image = new Image();
                    await new Promise((resolve, reject) => { image.onload = resolve; image.onerror = reject; image.src = dimensionsObjectUrl; });
                    dimensions = { width: image.naturalWidth, height: image.naturalHeight };
                } catch (error) {
                    dimensions = { width: null, height: null };
                } finally {
                    URL.revokeObjectURL(dimensionsObjectUrl);
                }
            }

            if (!(fileVersion === selectedFileVersion && dialog.isConnected)) { return; }
            newPreview.innerHTML = previewMarkup({ label: neo__("New file", "Neue Datei"), url: selectedObjectUrl, fileType: fileType, filename: file.name, fileSize: file.size, width: dimensions.width, height: dimensions.height, includeProgressbar: true, previewTabIndex: -1 });
            replaceButton.disabled = false;
        };

        const closeDialog = () => {
            if (progress) { return; }
            finishDialog();
        };

        const onKeyPress = (event) => {
            if (Swal.isVisible()) { return; }
            if (event.key === "Escape" && !mediaPreviewPopup.hasAttribute("open")) {
                closeDialog(); event.preventDefault(); event.stopImmediatePropagation(); return;
            };
            if (event.key !== "Tab") { return; }
            const focusableNodes = [...dialog.querySelectorAll([
                "a[href]",
                "button:not(:disabled):not([tabindex=\"-1\"])",
                "input:not(:disabled):not([type=\"hidden\"]):not(.neo-replace--dialog-file-input)",
                "select:not(:disabled)",
                "textarea:not(:disabled)",
                "[tabindex]:not([tabindex=\"-1\"])",
                "neo-button-neo-replace:not([disabled])",
            ].join(", "))].filter((node) => node.getClientRects().length > 0);
            if (focusableNodes.length === 0) { closeButton.focus(); event.preventDefault(); event.stopImmediatePropagation(); return; }
            if (!dialog.contains(document.activeElement)) { focusableNodes[0].focus(); event.preventDefault(); event.stopImmediatePropagation(); return; }
            const firstFocusableNode = focusableNodes[0]; const lastFocusableNode = focusableNodes[focusableNodes.length - 1];
            if (event.shiftKey && document.activeElement === firstFocusableNode) { lastFocusableNode.focus(); event.preventDefault(); event.stopImmediatePropagation(); return; }
            if (!event.shiftKey && document.activeElement === lastFocusableNode) { firstFocusableNode.focus(); event.preventDefault(); event.stopImmediatePropagation(); }
        }

        window.addEventListener("keydown", onKeyPress);
        observeClick(closeButton, closeDialog);
        observeClick(dialog.querySelector(".neo-replace--dialog-backdrop"), closeDialog);
        filenameCheckbox.addEventListener("change", (event) => localStorage.setItem("neo-replace--dialog-use-uploaded-filename", event.target.checked ? "true" : "false"));
        dateCheckbox.addEventListener("change", (event) => localStorage.setItem("neo-replace--dialog-update-upload-date", event.target.checked ? "true" : "false"));
        observeClick(dropzone, (node, event) => { if (event.target === fileInput) { return; } fileInput.click(); });
        dropzone.addEventListener("keydown", (event) => { if (!["Enter", " "].includes(event.key)) { return; } event.preventDefault(); fileInput.click(); });
        dialog.addEventListener("dragover",  (event) => { event.preventDefault(); event.stopPropagation(); dialog.classList.toggle("neo-replace--dialog-dragover", Array.from(event.dataTransfer?.types || []).includes("Files")); }, true);
        dialog.addEventListener("dragleave", (event) => { event.stopPropagation(); dialog.classList.remove("neo-replace--dialog-dragover"); }, true);
        dialog.addEventListener("drop", async (event) => { event.preventDefault(); event.stopPropagation(); dialog.classList.remove("neo-replace--dialog-dragover"); if (!(event.dataTransfer?.files?.length > 0)) { return; } await setSelectedFile(event.dataTransfer.files[0]); }, true);
        fileInput.addEventListener("change", async () => await setSelectedFile(fileInput.files[0]));

        dialog.addEventListener("click", (event) => {
            const preview = event.target.closest(".neo-replace--dialog-preview");
            if (!preview) { return; }
            if (preview.closest(".neo-replace--dialog-dropzone")) { return; }
            const previewUrl = preview.getAttribute("data-neo-replace--preview-url");
            if (previewUrl) { mediaPreviewPopup.open(previewUrl); }
        });

        observeClick(replaceButton, async () => {
            if (!selectedFile || progress) { return; }
            progress = true;
            replaceButton.disabled = true;

            if (await isPagebuilderOpen()) {
                const response = await Swal.fire({ icon: "warning", title: neo__("Pagebuilder Open", "Pagebuilder geöffnet"), html: neo__("Please close all Pagebuilders and Edit-Tabs to avoid issues.", "Bitte schließe alle Pagebuilder und Edit-Tabs, um Probleme zu vermeiden."), showCancelButton: true, cancelButtonText: neo__("Cancel", "Abbrechen"), confirmButtonText: neo__("Continue", "Fortfahren"), focusConfirm: true, customClass: { container: "neo-replace--dialog-swal" } });
                if (!response.isConfirmed) { progress = false; replaceButton.disabled = false; return; }
            }

            dialog.classList.add("neo-replace--progress");
            replaceButton.classList.add("neo-replace--loading");
            filenameCheckbox.disabled = true; dateCheckbox.disabled = true;

            try {
                if (filenameCheckbox.checked) { await initCleanFileNameEmojiMap(); }
                const uploadFile = filenameCheckbox.checked ? new File([selectedFile], cleanPathRel(selectedFile.name, { keepExtension: true }), { type: selectedFile.type, lastModified: selectedFile.lastModified }) : selectedFile;
                const uploadTitle = filenameCheckbox.checked ? cleanTitle(selectedFile.name) : "";
                const uploadSlug = filenameCheckbox.checked ? cleanSlug(selectedFile.name) : "";
                const uploadReplacement = async (mainFile, sourceFile = null) => {
                    const formData = new FormData();
                    formData.append("img-url", info.imgUrl);
                    formData.append("file", mainFile, mainFile.name);
                    if (sourceFile) { formData.append("source-file", sourceFile, sourceFile.name); }
                    formData.append("use-uploaded-filename", filenameCheckbox.checked ? "true" : "false");
                    formData.append("clean-uploaded-title", uploadTitle);
                    formData.append("clean-uploaded-slug", uploadSlug);
                    formData.append("update-upload-date", dateCheckbox.checked ? "true" : "false");
                    dialog.style.setProperty("--neo-replace--dialog-progress", "0");
                    const replaceEndpointPath = "/wp-json/neo/replace";
                    try { await refreshNonceIfOutdated(); } catch (error) {}
                    return extractJson(await new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open("POST", jsVar("neoGlobalRestEndpoints")[replaceEndpointPath].url, true);
                        xhr.setRequestHeader("X-WP-Nonce", fetchEndpoint.nonce ?? jsVar("neoGlobalRestEndpointNonce"));
                        xhr.upload.onprogress = (event) => { if (event.lengthComputable && event.total > 0) { dialog.style.setProperty("--neo-replace--dialog-progress", String(Math.max(0.02, Math.min(1, event.loaded / event.total)))); } };
                        xhr.onload = () => { dialog.style.setProperty("--neo-replace--dialog-progress", "1"); if (xhr.status >= 200 && xhr.status < 300) { resolve(xhr.responseText); return; } try { const errorJson = extractJson(xhr.responseText); reject(new Error(errorJson?.message || xhr.responseText || neo__("Replacement failed", "Ersetzen fehlgeschlagen"))); } catch (error) { reject(error); } };
                        xhr.onerror = () => reject(new Error(neo__("Network error", "Netzwerkfehler")));
                        xhr.send(formData);
                    }));
                };
                let result = null;
                if (/\.(heic|heif)$/i.test(uploadFile.name)) {
                    if (!(window.wp?.uploadMedia?.MediaUploadProvider && window.wp?.data?.useDispatch && window.wp?.element?.createRoot && window.wp?.element?.useEffect)) { throw new Error(neo__("HEIC uploads require the WordPress 7.1 media pipeline.", "HEIC-Uploads benötigen die WordPress-7.1-Media-Pipeline.")); }
                    const sourceMimeType = uploadFile.name.toLowerCase().endsWith(".heif") ? "image/heif" : "image/heic";
                    const sourceFile = new File([uploadFile], uploadFile.name, { type: sourceMimeType, lastModified: uploadFile.lastModified });
                    const providerNode = document.createElement("div"); const providerRoot = window.wp.element.createRoot(providerNode);
                    try {
                        const attachment = await new Promise((resolve, reject) => {
                            const PipelineUpload = () => {
                                const pipelineDispatch = window.wp.data.useDispatch(window.wp.uploadMedia.store);
                                window.wp.element.useEffect(() => { const firstFrame = requestAnimationFrame(() => requestAnimationFrame(() => pipelineDispatch.addItems({ files: [sourceFile], allowedTypes: ["image"], onSuccess: ([uploadedAttachment]) => resolve(uploadedAttachment), onError: reject }))); return () => cancelAnimationFrame(firstFrame); }, [pipelineDispatch]);
                                return null;
                            };
                            providerRoot.render(window.wp.element.createElement(window.wp.uploadMedia.MediaUploadProvider, { settings: { mediaUpload: async ({ filesList, onSuccess, onError }) => { try { const replaceResult = await uploadReplacement(filesList[0], sourceFile); onSuccess([{ url: replaceResult.imgUrl, neoReplaceResult: replaceResult }]); } catch (error) { onError(error); } }, allowedMimeTypes: jsVar("neoReplaceAllowedMimeTypes"), maxUploadFileSize: jsVar("neoReplaceMaxUploadFileSize"), allImageSizes: {} } }, window.wp.element.createElement(PipelineUpload)));
                        });
                        result = attachment.neoReplaceResult;
                    } finally {
                        providerRoot.unmount();
                    }
                } else {
                    result = await uploadReplacement(uploadFile);
                }
                didReplace = true;
                replaceButton.classList.remove("neo-replace--loading");
                replaceButton.classList.add("neo-replace--done");
                dialog.classList.remove("neo-replace--progress");
                dialog.classList.add("neo-replace--success");
                replaceButton.querySelector(".neo-replace--dialog-replace-button-text").textContent = neo__("Done", "Fertig");
                onUpdateCallback({ oldImgUrl: info.imgUrl, newImgUrl: result.imgUrl, result });
                progress = false; await delay(2); finishDialog();
            } catch (error) {
                progress = false;
                dialog.classList.remove("neo-replace--progress");
                replaceButton.classList.remove("neo-replace--loading");
                replaceButton.disabled = false;
                filenameCheckbox.disabled = false; dateCheckbox.disabled = false;
                await Swal.fire({ icon: "error", title: neo__("Replacement failed", "Ersetzen fehlgeschlagen"), text: error.message, customClass: { container: "neo-replace--dialog-swal" } });
            }
        });
    });
}

export async function interfaceOpenReplaceDialog20260525({ imgUrl, onUpdateCallback = () => {} } = {}) { return openReplaceDialog({ imgUrl, onUpdateCallback }); }
