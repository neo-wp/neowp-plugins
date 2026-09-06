import { neoError, neoWarn } from "./_global--log.js";
import { domLoaded } from "./_global--observer.js";
import { fitProtocolToFetchImgUrl, getQueryParam, getUrlFilename, hasQueryParam, removeAllQueryParams, removeQueryParam } from "./_global--url-helper.js";
import { extractJson } from "./_global--extract-json.js";
import { isNeoDrawImage, parseMetadata } from "./_global-image-metadata.js";
import { neo__ } from "./_global--translation.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { isInterfaceFunctionErrorMessage } from "./_global--interface.js";

import { neoLoadInterfaceFunc } from "./_global--interface.js";

import { motionFrameCutPixels } from "./_global--motion-frame.js";
const { loadFromBlob, exportToSvg, exportToCanvas, exportToBlob, parseLibraryTokensFromUrl } = window.ExcalidrawLib;

let loadSuccessful = false;

let state = {};
export function setState(newState) { state = newState; }
let stateSetters = {};
export function setStateSetters(setters) { stateSetters = setters; }

function showError({ message, closeEditorOnOk }) {
    window.parent.postMessage({ action: "error", data: { message, closeEditorOnOk } });
    if (loadSuccessful) {
        stateSetters.setSaveLoading(false);
        stateSetters.setErrorMessage(message);
        stateSetters.setCloseEditorOnErrorOk(closeEditorOnOk);
    } else {
        const editorIsHidden = getQueryParam(location.href, "is-hidden") === "true";
        if (!editorIsHidden) { alert(message); }
        if (closeEditorOnOk) { closeWithoutConfirmation(); }
    }
}

async function calculateExportPadding() {
    let padding;
    for (padding = 1; padding < 20; padding++) {
        let sceneElements = state.excalidrawAPI.getSceneElements();
        sceneElements = sceneElements.map(element => {
            if (element.type === "image") {
                element = {
                    ...element,
                    type: "rectangle",
                    backgroundColor: "transparent", strokeColor: "transparent", fillStyle: "solid"
                };
            }
            return element;
        });

        const canvas = await exportToCanvas({
            elements: sceneElements,
            appState: {...state.excalidrawAPI.getAppState(), exportBackground: false },
            files: state.excalidrawAPI.getFiles(),
            exportPadding: padding,
        });
        if (!(canvas.width * canvas.height < 512 * 1024 * 1024)) { return 0; }

        const imageData = canvas.getContext("2d").getImageData(0, 0, Math.max(1, canvas.width), Math.max(1, canvas.height));
        let hasEmptyBorder = true;
        for (let x = 0; x < canvas.width; x++) {
            const topBorderTransparent = imageData.data[x * 4 + 3] === 0;
            const bottomBorderTransparent = imageData.data[(canvas.height - 1) * canvas.width * 4 + x * 4 + 3] === 0;
            if (!topBorderTransparent || !bottomBorderTransparent) { hasEmptyBorder = false; break; }
        }
        for (let y = 0; y < canvas.height; y++) {
            const leftBorderTransparent = imageData.data[y * canvas.width * 4 + 3] === 0;
            const rightBorderTransparent = imageData.data[y * canvas.width * 4 + (canvas.width - 1) * 4 + 3] === 0;
            if (!leftBorderTransparent || !rightBorderTransparent) { hasEmptyBorder = false; break; }
        }
        if (hasEmptyBorder) { padding = padding - 1; break; }
    }
    return padding;
}

async function getExcalidrawExportSettings(mimeType) {
    if (!["image/png", "image/jpeg", "image/webp", "image/svg+xml"].includes(mimeType)) { throw new Error(`Unsupported MIME type: ${mimeType}`); }
    const exportSettings = {};
    exportSettings.elements                  = structuredClone(state.excalidrawAPI.getSceneElements());
    exportSettings.appState                  = structuredClone(state.excalidrawAPI.getAppState());
    exportSettings.files                     = structuredClone(state.excalidrawAPI.getFiles());
    exportSettings.appState.exportEmbedScene = true; exportSettings.appState.exportBackground = false;
    exportSettings.appState.frameRendering   = { enabled: false };

    if (state.excalidrawAPI.getSceneElements().some(e => e.type === "frame")) {
        if (mimeType !== "image/svg+xml") {
            exportSettings.exportPadding = -motionFrameCutPixels();
        }
    } else {
        exportSettings.exportPadding = await calculateExportPadding();
    }
    return exportSettings;
}

export async function exportSvgAsString(frameId = null) {
    const exportSettings = await getExcalidrawExportSettings("image/svg+xml");

    let stopMotionAnimationFrameElements = [];
    try {
        const motionFramePreparation = await (await neoLoadInterfaceFunc("neo-animate", "neo-motion--draw-export.js", "interfacePrepareNeoDrawMotionFramesSuppressErrorPopup20260607"))({ elements: exportSettings.elements, frameId });
        stopMotionAnimationFrameElements = motionFramePreparation.stopMotionAnimationFrameElements; exportSettings.elements = motionFramePreparation.elements;
        if (motionFramePreparation.elementsForSceneUpdate) { state.excalidrawAPI.updateScene({ elements: motionFramePreparation.elementsForSceneUpdate }); }
    } catch (e) {
        if (!isInterfaceFunctionErrorMessage(e.message)) { throw e; }
        stopMotionAnimationFrameElements = [];
    }

    if (frameId) {
        exportSettings.appState.selectedElementIds = {}; exportSettings.appState.selectedElementIds[frameId] = true;

        const { exportedElements, exportingFrame } = prepareElementsForExport(exportSettings.elements, exportSettings.appState, true);
        exportSettings.elements = exportedElements.filter(elem => elem.frameId === frameId);
        exportSettings.exportingFrame = exportingFrame;
    }

    const exportedSvgNode = await exportToSvg(exportSettings);

    if (exportedSvgNode.getAttribute("width")  === "0") { exportedSvgNode.setAttribute("width", "1"); }
    if (exportedSvgNode.getAttribute("height") === "0") { exportedSvgNode.setAttribute("height", "1"); }

    for (let aTag of exportedSvgNode.querySelectorAll("a")) {
        aTag.setAttribute("target", "_blank");
    }

    let preparedSvgHeader = exportedSvgNode.outerHTML.split(">")[0] + ">";

    let metadata = {
        pluginVersion: jsVar("neoDrawEditorPluginVersion"),
        wpSiteUrl:     jsVar("neoDrawEditorWpSiteUrl"),
        lastModified:  Date.now(),
        width:         Math.round(parseFloat(exportedSvgNode.getAttribute("width"))),
        height:        Math.round(parseFloat(exportedSvgNode.getAttribute("height"))),
        isLinked:      Boolean(exportedSvgNode.querySelector("a")),
        isAnimated:    null,
        isMotion:      false,
        headerEnd:     state.metadata?.headerEnd     ?? null,
    };

    for (const [key, value] of Object.entries(state.metadata ?? {})) {
        if (!(key in metadata)) { metadata[key] = value; }
    }

    let preparedAnimatedCssStyle;
    try {
        ({ metadata, preparedAnimatedCssStyle } = await (await neoLoadInterfaceFunc("neo-animate", "neo-animate--draw-export.js", "interfacePrepareNeoDrawAnimationExportSuppressErrorPopup20260607"))({ metadata, exportedSvgNode }));
    } catch (e) {
        if (!isInterfaceFunctionErrorMessage(e.message)) { throw e; }
        metadata.isAnimated = false; preparedAnimatedCssStyle = "<!-- No animations -->";
    }

    let exportedInnerHtmlSvgString = exportedSvgNode.innerHTML;
    const excalidrawDataRegex = /<!--\s*svg-source:excalidraw\s*-->\s*(?:<metadata[\s\S]*?<!--\s*payload-end\s*-->\s*<\/metadata>|<!--\s*payload-type:application\/vnd\.excalidraw\+json\s*-->[\s\S]*?<!--\s*payload-end\s*-->)/s;
    let preparedExcalidrawData = exportedInnerHtmlSvgString.match(excalidrawDataRegex)[0];
    preparedExcalidrawData = preparedExcalidrawData.replace(/\n\s+/g, "\n");
    if (!preparedExcalidrawData) { throw new Error("Failed to get Excalidraw data from SVG."); }
    exportedInnerHtmlSvgString = exportedInnerHtmlSvgString.replace(excalidrawDataRegex, "");
    exportedSvgNode.innerHTML = exportedInnerHtmlSvgString;

    let preparedPixelVariant = "";
    if (!frameId) {
        const { width, height, quality, blob} = await exportPngOrJpgOrWebp("image/webp", (1920));
        const webpBase64 = await new Promise((resolve, reject) => {
            const reader = new FileReader(); reader.readAsDataURL(blob);
            reader.onloadend = () => resolve(reader.result); reader.onerror = reject;
        });
        preparedPixelVariant = `<!-- ${width}x${height}q${Math.round(quality * 100)} ${webpBase64} -->`;
    }

    const defs = exportedSvgNode.querySelector("defs");
    defs.innerHTML = defs.innerHTML.trim();
    let preparedExcalidrawDefs = window.xmlFormat(defs.outerHTML, { indentation: "  ", lineSeparator: "\n" });
    defs.remove();

    let preparedSymbols = "";
    for (const symbol of exportedSvgNode.querySelectorAll("symbol")) {
        preparedSymbols += symbol.outerHTML + '\n';
        symbol.remove();
    }
    preparedSymbols = preparedSymbols.trim();

    const dummyNodeForFormatting = document.createElement("div");
    dummyNodeForFormatting.innerHTML = window.xmlFormat(exportedSvgNode.outerHTML, { indentation: '  ', collapseContent: true, lineSeparator: '\n' });
    let preparedSvgRest = dummyNodeForFormatting.querySelector("svg").innerHTML;
    preparedSvgRest = preparedSvgRest.replace(/\n  /g, "\n");
    preparedSvgRest = preparedSvgRest.replace(/^\n+/, '').replace(/\n+$/, '');

    const indent = (codeBlock) => codeBlock.split("\n").map(line => "  " + line).join("\n");
    const unindent = (codeBlock) => codeBlock.split("\n").map(line => line.replace(/^  /, "")).join("\n");
   let preparedMotionCssStyle = "<!-- No stop motion -->";
   try {
       ({ preparedExcalidrawDefs, preparedSymbols, preparedSvgRest, preparedSvgHeader, preparedMotionCssStyle, metadata } = await (await neoLoadInterfaceFunc("neo-animate", "neo-motion--draw-export.js", "interfacePrepareNeoDrawMotionExportSuppressErrorPopup20260607"))({ stopMotionAnimationFrameElements, frameId, preparedExcalidrawDefs, preparedSymbols, preparedSvgRest, preparedSvgHeader, metadata, exportSvgAsString, indent, unindent }));
   } catch (e) {
       if (!isInterfaceFunctionErrorMessage(e.message)) { throw e; }
       preparedMotionCssStyle = "<!-- No stop motion -->";
   }
    preparedExcalidrawDefs = preparedExcalidrawDefs.replace(/\s*<defs>\s*<style>\s*<\/style>\s*<\/defs>/g, "<!-- No defs -->");

    if (preparedSvgRest.includes('data-neo-animate--id="idundefined"')) { neoWarn("SVG contains element with idundefined. This is a bug."); }

    return `<?xml version="1.0" encoding="UTF-8"?>
${preparedSvgHeader}

<!-- Created with neoDraw -->

<!-- START - neoDraw metadata -->
<!-- ${JSON.stringify(metadata)} -->
<!-- END - neoDraw metadata -->

<!-- START - Excalidraw data -->
${preparedExcalidrawData}
<!-- END - Excalidraw data -->

<!-- START - Pixel variant -->
${preparedPixelVariant}
<!-- END - Pixel variant -->

<!-- START - Excalidraw defs -->
${indent(preparedExcalidrawDefs).trimEnd()}
<!-- END - Excalidraw defs -->

<!-- START - neoMotion CSS -->
${indent(preparedMotionCssStyle).trimEnd()}
<!-- END - neoMotion CSS -->

<!-- START - neoAnimate CSS -->
${indent(preparedAnimatedCssStyle).trimEnd()}
<!-- END - neoAnimate CSS -->

<!-- START - Symbols -->
${indent(preparedSymbols || `<!-- No symbols -->`).trimEnd()}
<!-- END - Symbols -->

<!-- START - SVG Nodes -->
${indent(preparedSvgRest).trimEnd()}
<!-- END - SVG Nodes -->

</svg>`;
}
window.neoDrawExportSvgAsString = exportSvgAsString;

async function exportPngOrJpgOrWebp(mimeType, maxSize = undefined) {
    const exportSettings = await getExcalidrawExportSettings(mimeType);

    if (mimeType === "image/jpeg") { exportSettings.quality = 0.92; } else if (mimeType === "image/webp") { exportSettings.quality = 0.60; }
    exportSettings.appState.exportBackground = mimeType === "image/jpeg" ? true : false;
    exportSettings.appState.viewBackgroundColor = mimeType === "image/jpeg" ? "#fff" : "transparent";
    exportSettings.appState.exportEmbedScene = !maxSize;
    exportSettings.neoDrawMetadata = state.metadata ?? {};
    exportSettings.mimeType = mimeType;
    exportSettings.getDimensions = (width, height) => {
        width = Math.max(1, width); height = Math.max(1, height);
        let scale = 1;
        if (maxSize) { scale = Math.min(1.0, maxSize / Math.max(width, height)); width *= scale; height *= scale; }
        return { width, height, scale };
    };

    const stopMotionAnimationFrameElements = structuredClone(exportSettings.elements).filter(element => element.type === "frame" && !element.isDeleted && (!element.name || element.name.toLowerCase().startsWith("frame"))).sort((a, b) => (a.name ?? "zzzzzz").localeCompare(b.name ?? "zzzzzz", undefined, { numeric: true }));
    if (stopMotionAnimationFrameElements.length > 0) {
        const frameId = stopMotionAnimationFrameElements[0].id;
        exportSettings.appState.selectedElementIds = {}; exportSettings.appState.selectedElementIds[frameId] = true;

        const { exportedElements, exportingFrame } = prepareElementsForExport(exportSettings.elements, exportSettings.appState, true);
        exportSettings.elements = exportedElements.filter(elem => elem.frameId === frameId);
        exportSettings.exportingFrame = exportingFrame;
    }

    const blob = await exportToBlob(exportSettings);

    const [width, height] = await new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => { resolve([img.naturalWidth, img.naturalHeight]); URL.revokeObjectURL(img.src); }
        img.onerror = (error) => { reject(error); URL.revokeObjectURL(img.src); };
        img.src = URL.createObjectURL(blob);
    });

    return { width, height, quality: exportSettings.quality, blob };
}
window.neoDrawExportPngOrJpgOrWebp = exportPngOrJpgOrWebp;

window.neoDrawGetFilename = () => {
    if (!state.imgUrl) {
        return "neo-draw.svg";
    }
    return getUrlFilename(state.imgUrl);
};

export async function save({ closeAfterSave, usageCheck } = { closeAfterSave: false, usageCheck: true }) {
    stateSetters.setSaveLoading(true);

    try {
        let forceNew = getQueryParam(location.href, "force-new") === "true";
        let filename = getQueryParam(location.href, "default-filename");
        let forceTitle = false;

        if (usageCheck && localStorage.getItem("neo-draw--save-multi-usage-dialog-hidden") !== "true" && state.imgUrl && !state.imgUrl.startsWith("data:")) {
            const dbEntriesUsingImages = await fetchEndpoint("/wp-json/neo/draw-db-entries-using-images", { query: { "img-url": state.imgUrl } }).then(extractJson);
            const currentPostId = getQueryParam(location.href, "post-id");
            const getDbEntryPostId = (entry) => (entry.postId ?? entry.contentPreview?.post_id ?? entry.contentPreview?.ID ?? "no post found for entry").toString();
            const rowCountInOtherPosts = dbEntriesUsingImages.filter(entry => getDbEntryPostId(entry) !== currentPostId).length;

            let countInOwnPost = 0;
            const imgUrlRelative = new URL(removeAllQueryParams(state.imgUrl), location.origin).pathname;
            const countImageUsagesInDocument = (nodeToSearch) => { if (!nodeToSearch) { return 0; } const baseUrl = nodeToSearch.location?.href ?? nodeToSearch.ownerDocument?.location?.href ?? location.href; return [...nodeToSearch.querySelectorAll("img")].filter(img => new URL(removeAllQueryParams(img.getAttribute("src") || ""), baseUrl).pathname === imgUrlRelative).length; };
            if (window.parent !== window) {
                try {
                    const parentDocument = window.parent.document;
                    if (parentDocument.getElementById("elementor-preview-iframe")) {
                        countInOwnPost = countImageUsagesInDocument(parentDocument.getElementById("elementor-preview-iframe").contentWindow.document);
                    } else if (parentDocument.getElementById("content_ifr")) {
                        countInOwnPost = countImageUsagesInDocument(parentDocument.getElementById("content_ifr").contentWindow.document);
                    } else if (parentDocument.querySelector('iframe[name="editor-canvas"]')) {
                        countInOwnPost = countImageUsagesInDocument(parentDocument.querySelector('iframe[name="editor-canvas"]').contentWindow.document);
                    } else if (parentDocument.querySelector("#editor.block-editor__container")) {
                        countInOwnPost = countImageUsagesInDocument(parentDocument.querySelector("#editor.block-editor__container .edit-post-visual-editor"));
                    }
                } catch (error) {
                    neoWarn("Could not count neoDraw image usages in parent editor: " + error.message);
                }
            }

            if (countInOwnPost + rowCountInOtherPosts > 1) {
                let answerResolve;
                const answerPromise = new Promise(resolve => answerResolve = resolve);
                stateSetters.setSaveMultiUsageDialogOnAnswer(() => { return answer => answerResolve(answer); });
                stateSetters.setSaveMultiUsageDialogCountInCurrentPost(countInOwnPost);
                stateSetters.setSaveMultiUsageDialogOpen(true);
                const multiUsageSaveAnswer = await answerPromise;

                stateSetters.setSaveMultiUsageDialogOpen(false);
                await new Promise(resolve => setTimeout(resolve, 0));

                if (multiUsageSaveAnswer === "cancel") {
                    stateSetters.setSaveLoading(false);
                    return;
                } else if (multiUsageSaveAnswer === "createNew") {
                    forceNew = true;
                    filename = getUrlFilename(state.imgUrl);
                }
            }
        }

        if ((state.imgUrl === "" || forceNew) && getQueryParam(location.href, "is-hidden") !== "true") {
            let answerResolve;
            const answerPromise = new Promise(resolve => answerResolve = resolve);
            stateSetters.setFilenameDialogDefaultFilename(filename || (state.insertedFromImgUrl ? getUrlFilename(state.insertedFromImgUrl) : "neo-draw"));
            stateSetters.setFilenameDialogOnAnswer(() => answer => answerResolve(answer));
            stateSetters.setFilenameDialogOpen(true);
            filename = await answerPromise;
            if (filename === null) { stateSetters.setSaveLoading(false); return; }
            forceTitle = filename;
            await new Promise(resolve => setTimeout(resolve, 0));
        }

        const elements = state.excalidrawAPI.getSceneElements();
        const newElements = structuredClone(elements);
        const frames = newElements.filter(e => e.type === "frame");
        for (const frame of frames) {
            for (const e of window.neoDrawElementsOverlappingBBox({ elements: newElements, bounds: frame, type: "overlap" })) {
                if (e.frameId) { continue; }
                if (e.type === "frame") { continue; }
                e.frameId = frame.id;
            }
        }
        if (JSON.stringify(newElements) !== JSON.stringify(elements)) { state.excalidrawAPI.updateScene({ elements: newElements, commitToStore: false }); }

        const exportedAsString = await exportSvgAsString();

        let imgId; let savedImgUrl = state.imgUrl;
        const savingPromise = (async () => {
            console.debug("Saving image", { exportedAsString, imgUrl: state.imgUrl });

            const postId = getQueryParam(location.href, "post-id");

            const jsonResponse = await fetchEndpoint("/wp-json/neo/editor", {
                method: "POST",
                body: {
                    "svg": exportedAsString,
                    "img-url":               state.imgUrl,
                    "post-id":               postId,
                    "inserted-from-img-url": state.insertedFromImgUrl,
                    "force-new":             forceNew,
                    "filename":              filename,
                    "force-title":           forceTitle,
                }
            }).then(extractJson);

            const imgUrl = jsonResponse.imgUrl; const imgId = jsonResponse.imgId;
            console.debug("Image saved", { imgUrl, imgId });
            return { imgUrl, imgId };
        })()
        .then(details => {
            savedImgUrl = details.imgUrl; stateSetters.setImgUrl(savedImgUrl); stateSetters.setInsertedFromImgUrl(null);
            if (forceNew) { history.replaceState({}, "", removeQueryParam(removeQueryParam(location.href, "force-new"), "default-filename")); }
            imgId = details.imgId;
        });

        const showTimePromise = new Promise(resolve => setTimeout(resolve, 250));

        await Promise.all([savingPromise, showTimePromise]);

        stateSetters.setSaveLoading(false);

        stateSetters.setIsDirty(false);

        if (!closeAfterSave && getQueryParam(location.href, "is-hidden") !== "true") {
            state.excalidrawAPI.setToast({ message: neo__("Saved", "Gespeichert"), duration: 2000 });
        }

        window.parent.postMessage({ action: "save", data: { imgUrl: savedImgUrl, imgId: imgId } });

        if (closeAfterSave) {
            closeWithoutConfirmation();
        }
    } catch (error) {
        neoError(error);
        let errorMessage = error.message;
        if (errorMessage === "Failed to fetch") {
            if (!navigator.onLine) {
                errorMessage = neo__("You are offline.", "Du bist offline.");
            } else {
                errorMessage = neo__("The server is not reachable.", "Der Server ist nicht erreichbar.");
            }
        }
        showError({ message: neo__("Error: Could not save diagram: ", "Error: Konnte das Diagramm nicht speichern: ") + errorMessage + "\n" + error.stack, closeEditorOnOk: false });
        stateSetters.setSaveLoading(false);
        throw error;
    }
}
window.neoDrawSave = save;

export async function exportAndDownload() {
    const svgString = await exportSvgAsString();
    const svgUrl = URL.createObjectURL(new Blob([svgString], { type: "image/svg+xml;charset=utf-8" }));

    const downloadLink = document.createElement("a");
    downloadLink.href = svgUrl;
    downloadLink.download = state.imgUrl ? getUrlFilename(state.imgUrl) : "neo-draw.svg";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
window.neoDrawExportAndDownload = exportAndDownload;

domLoaded(async () => {
    let imgUrl = getQueryParam(location.href, "img-url") || "";
    if (imgUrl === "base64") { imgUrl = await new Promise(resolve => {
        const timeout = setTimeout(() => { window.removeEventListener("message", receiveBase64Url); showError({ message: "Timed out while waiting for base64 image data.", closeEditorOnOk: true }); resolve(""); }, 10000);
        const receiveBase64Url = (event) => {
            if (event.source !== window.parent || event.data?.action !== "loadBase64Url") { return; }
            clearTimeout(timeout);
            window.removeEventListener("message", receiveBase64Url);
            resolve(typeof event.data.data === "string" ? event.data.data : "");
        };
        window.addEventListener("message", receiveBase64Url);
        window.parent.postMessage({ action: "readyForBase64Url" }, "*");
    }); if (!imgUrl) { return; } }
    let imgResponse, imgBlob;
    if (imgUrl) {
        try {
            const imgUrlWithHttps = fitProtocolToFetchImgUrl(imgUrl);
            imgResponse = await fetch(imgUrlWithHttps, { cache: "no-cache" });
            if (!imgResponse.ok) {
                neoError("Server error while downloading image", imgResponse); showError({ message: "Server error while downloading image", closeEditorOnOk: true });
                return;
            }
            imgBlob = await imgResponse.blob();
        } catch (error) {
            neoError("Network error downloading image", error); showError({ message: "Network error downloading image", closeEditorOnOk: true });
            return;
        }
    }
    const loadedImageMimeType = (imgResponse?.headers?.get("Content-Type") || imgBlob?.type || imgUrl.match(/^data:([^;,]+)/)?.[1] || "image/png").split(";")[0].trim().toLowerCase();

    while (!state.excalidrawAPI) { await new Promise((resolve) => setTimeout(resolve, 20)); }
    if (imgUrl === "") {
        stateSetters.setMetadata({});
    } else {
        let excalidrawOpenSuccessful = false;
        if (!hasQueryParam(location.href, "force-insert")) {
            try {
                const data = await loadFromBlob(imgBlob, null, null, null);
                state.excalidrawAPI.addFiles(Object.values(data.files));
                state.excalidrawAPI.updateScene(data);
                if (data.neoDrawMetadata) { stateSetters.setMetadata(data.neoDrawMetadata); }
                state.excalidrawAPI.history.clear(); state.excalidrawAPI.updateScene(data, {commitToStore: true});

                window.parent.postMessage({ action: "open", data: imgUrl });
                excalidrawOpenSuccessful = true;
            } catch (error) {
                console.debug(error);
                console.debug("Excalidraw open failed. Inserting image instead.", imgUrl.slice(0, 200));
                excalidrawOpenSuccessful = false;
            }
        }

        if (excalidrawOpenSuccessful) {
            stateSetters.setImgUrl(imgUrl);

            try {
                const imgMimetype = loadedImageMimeType;
                if (imgMimetype === "image/svg+xml") {
                    const reader = new FileReader(); reader.readAsText(imgBlob);
                    const svgContent = await new Promise((resolve, reject) => { reader.onloadend = () => resolve(reader.result); reader.onerror = reject; });
                    if (isNeoDrawImage(svgContent)) {
                        const metadata = parseMetadata(svgContent);
                        stateSetters.setMetadata(metadata);
                    }
                }
            } catch (error) {
                neoError(error); showError({ message: neo__("Failed to fetch and parse the image metadata.", "Das Abrufen und Parsen der Bild-Metadaten ist fehlgeschlagen."), closeEditorOnOk: true });
                return;
            }
        } else {
            if (hasQueryParam(location.href, "suppress-insert")) {
                window.parent.postMessage({ action: "error", data: { message: "Inserting is suppressed in this editor.", closeEditorOnOk: true } });
                return;
            }

            let imgBase64;
            try {
                if (imgUrl.startsWith("data:image/")) { imgBase64 = imgUrl; }
                else {
                    const reader = new FileReader(); reader.readAsDataURL(imgBlob);
                    imgBase64 = await new Promise((resolve, reject) => { reader.onloadend = () => resolve(reader.result); reader.onerror = reject; });
                }
            } catch (error) {
                neoError(error); showError({ message: "Failed to fetch and convert the image to base64.", closeEditorOnOk: true });
                return;
            }

            const embeddedFileId = "embedded-file-" + Date.now();
            const files = {
                "embedded": {
                    created: Date.now(),
                    dataURL: imgBase64,
                    id: embeddedFileId,
                    lastRetrieved: Date.now(),
                    mimeType: loadedImageMimeType,
                }
            };

            const img = new Image(); img.src = imgBase64;
            await new Promise((resolve, reject) => { img.onload = resolve; img.onerror = reject; });
            const width = img.width; const height = img.height;

            const elements = [{
                type: "image", fileId: embeddedFileId,
                id: "embedded-" + Date.now(),
                opacity: 100, backgroundColor: "transparent", fillStyle: "hachure",
                strokeStyle: "solid", strokeColor: "transparent", strokeWidth: 1, seed: 0, roughness: 1, roundness: null,
                isDeleted: false, link: null, locked: false, groupIds: [], boundElements: null,
                status: "pending", updated: Date.now(), version: 4, versionNonce: 715528756,
                x: 0, y: 0, scale: [1, 1], angle: 0, width, height
            }];

            state.excalidrawAPI.updateScene({ elements, files });
            state.excalidrawAPI.addFiles(Object.values(files));
            state.excalidrawAPI.updateScene({ appState: { currentItemStrokeColor: "#e03131", currentItemStrokeWidth: 8 } }); state.excalidrawAPI.setActiveTool({ type: "rectangle" });

            stateSetters.setImgUrl("");

            stateSetters.setInsertedFromImgUrl(imgUrl);

            stateSetters.setMetadata({});
        }

        if (state.excalidrawAPI.getSceneElements().length > 0) {
            state.excalidrawAPI.scrollToContent(undefined, { fitToViewport: true, viewportZoomFactor: 0.7 });
        }
    }

    window.parent.postMessage({ action: "load" });

    loadSuccessful = true;

    stateSetters.setIsDirty(false);
});

function closeWithoutConfirmation() {
    window.parent.postMessage({ action: "close", data: state.imgUrl });
}

export async function close(evt) {
    if (state.isDirty) {
        let saveAnswer;

        if (evt.altKey) {
            saveAnswer = "save";
        } else {
            stateSetters.setSaveDialogOpen(true);

            let answerResolve;
            const answerPromise = new Promise((resolve) => {
                answerResolve = resolve;
            });
            stateSetters.setSaveDialogOnAnswer(() => { return answer => answerResolve(answer); });
            saveAnswer = await answerPromise;
        }

        if (saveAnswer === "save") {
            await save({ closeAfterSave: true, usageCheck: true });
        } else if (saveAnswer === "discard") {
            closeWithoutConfirmation();
        } else if (saveAnswer === "cancel") {

        }
    } else {
        closeWithoutConfirmation();
    }
}

const channel = new BroadcastChannel("neo-draw--iframe");
channel.addEventListener("message", async (event) => {
    console.debug("neo-draw--iframe BroadcastChannel received:", event.data);
    const { action, hash } = event.data ?? {};
    if (action === "addLibrary") {
        if (typeof hash !== "string") { return; }
        location.hash = hash;
        const { libraryUrl } = parseLibraryTokensFromUrl();

        const libraryPromise = new Promise(async (resolve, reject) => {
            try {
                const request = await fetch(decodeURIComponent(libraryUrl));
                if (!request.ok) { throw new Error(`HTTP ${request.status} ${request.statusText}`); }
                const blob = await request.blob();
                resolve(blob);
            } catch (error) {
                showError({ message: neo__("Error fetching library item:", "Fehler beim Abrufen des Bibliothekselements:") + " " + error.message, closeEditorOnOk: false });
                reject(error);
            }
        });
        await state.excalidrawAPI.updateLibrary({
            libraryItems: libraryPromise,
            prompt: false,
            merge: true,
            defaultStatus: "published",
            openLibraryMenu: true,
        });

        channel.postMessage({ action: "addLibraryConfirm" });

        stateSetters.setLibraryDialogUrl(null);
    }
});

document.addEventListener("click", (event) => {
    if (event.target.classList.contains("library-menu-browse-button")) {
        event.preventDefault();
        let libraryUrl = event.target.href;

        libraryUrl = libraryUrl.replace(location.hash, "");
        stateSetters.setLibraryDialogUrl(libraryUrl);
    }
});

window.dev ??= {};

window.dev.getExcalidrawData = () => { return { elements: state.excalidrawAPI.getSceneElements(), appState: state.excalidrawAPI.getAppState() }; };

window.dev.modifyExcalidraw = (modifyFunction) => {
    let { elements, appState } = window.dev.getExcalidrawData();
    elements = structuredClone(elements); appState = structuredClone(appState);
    const modifyResult = modifyFunction(elements, appState);
    if (!modifyResult) { throw new Error("modifyFunction must return an object with elements and appState."); }
    state.excalidrawAPI.updateScene({...modifyResult, commitToStore: true});
};

window.dev.modifyElementsByType = (type, modifyFunction) => { window.dev.modifyExcalidraw((elements, appState) => { elements.forEach(e => { if (e.type === type) { modifyFunction(e); } }); return { elements, appState }; }); };

window.dev.modifySelectedElements = (modifyFunction, type) => { window.dev.modifyExcalidraw((elements, appState) => { elements.forEach(e => { if (appState.selectedElementIds[e.id] && (!type || e.type === type)) { modifyFunction(e); } }); return { elements, appState }; }); };

window.dev.moveElementsIntoFrames = () => {
    window.dev.modifyExcalidraw((elements, appState) => {
        const frames = elements.filter(e => e.type === "frame");
        for (const frame of frames) {
            for (const e of window.neoDrawElementsOverlappingBBox({ elements, bounds: frame, type: "overlap" })) {
                if (e.frameId) { continue; }
                if (e.type === "frame") { continue; }
                e.frameId = frame.id;
            }
        }
        return { elements, appState };
    });
};

window.dev.positionSelectedFramesNextToEachOther = (distanceBetween, vertical = false) => {
    distanceBetween ??= 0;
    window.dev.moveElementsIntoFrames();
    let nextFrameX = null;
    let firstFrameY = null;
    window.dev.modifyExcalidraw((elements, appState) => {
        const selectedFrames = elements.filter(e => appState.selectedElementIds[e.id] && e.type === "frame");
        selectedFrames.sort((a, b) => Math.round(a.y / 20) * 20 - Math.round(b.y / 20) * 20 || Math.round(a.x / 20) * 20 - Math.round(b.x / 20) * 20);
        for (const frame of selectedFrames) {
            if (nextFrameX != null) {
                const frameXBefore = frame.x, frameYBefore = frame.y;
                if (!vertical) {
                    frame.x = nextFrameX;
                    frame.y = firstFrameY;
                } else {
                    frame.x = firstFrameY;
                    frame.y = nextFrameX;
                }

                elements = elements.map((e) => {
                    if (e.frameId === frame.id) { e.x += frame.x - frameXBefore; e.y += frame.y - frameYBefore; }
                    return e;
                });
            }
            if (!vertical) {
                nextFrameX = frame.x + frame.width + distanceBetween;
                firstFrameY ??= frame.y;
            } else {
                nextFrameX = frame.y + frame.height + distanceBetween;
                firstFrameY ??= frame.x;
            }
        }
        return { elements, appState };
    });
};

window.dev.removeSelectedFramesAndKeepElements = () => {
    window.dev.modifyExcalidraw((elements, appState) => {
        const selectedFrames = elements.filter(e => appState.selectedElementIds[e.id] && e.type === "frame");
        for (const frame of selectedFrames) {
            const elementFrames = elements.filter(e => e.frameId === frame.id);
            for (const e of elementFrames) { e.frameId = null; }
            for (const e of elementFrames) { appState.selectedElementIds[e.id] = true; }
            elements = elements.filter(e => e.id !== frame.id); delete appState.selectedElementIds[frame.id];
        }
        return { elements, appState };
    });
};

window.dev.changeScribble = (roughness) => { window.dev.modifyExcalidraw((elements, appState) => { elements = elements.map(e => { if (e.roughness != null) { e.roughness = roughness; } return e; }); return { elements, appState }; }); };

window.dev.changeFont = (fontFamily) => { window.dev.modifyExcalidraw((elements, appState) => { elements = elements.map(e => { if (e.fontFamily != null) { e.fontFamily = fontFamily; } return e; }); return { elements, appState }; }); };

window.dev.logExcalidrawData = () => { console.log(window.dev.getExcalidrawData()); };

window.dev.selectElementsByFilter = (filter) => { window.dev.modifyExcalidraw((elements, appState) => {
    appState.selectedElementIds = {};
    for (const e of elements.filter(filter)) { appState.selectedElementIds[e.id] = true; }
    return { elements, appState };
}); };

window.dev.selectElementsByType = (type) => { window.dev.selectElementsByFilter(e => e.type === type); };
