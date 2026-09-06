import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";
import { neoWarn } from "./_global--log.js";
import { uploadsUrl } from "./_global-plugin-and-uploads-url.js";
import { neo__ } from "./_global--translation.js";

const imageWebpFilePromises = new Map();

export async function imageToWebpFile(imageSource, { fileName = "image.webp", maxSize = 1920, minSize = null, sourceMinShortSide = 200, targetBytes = 1.5 * 1024 * 1024, timeoutSeconds = 45, sourceTimeoutSeconds = 12 } = {}) {
    const isRawSvgSource = source => typeof source === "string" && source.trimStart().startsWith("<");
    const isSvgSource = source => source instanceof Blob ? source.type === "image/svg+xml" : typeof source === "string" && (isRawSvgSource(source) || new URL(source, location.href).pathname.toLowerCase().endsWith(".svg"));
    const resolvedMinSize = minSize ?? (isSvgSource(imageSource) ? 1024 : 0);
    const imageCacheKey = typeof imageSource === "string" && !isRawSvgSource(imageSource) ? JSON.stringify([imageSource, fileName, maxSize, resolvedMinSize, sourceMinShortSide, targetBytes, timeoutSeconds, sourceTimeoutSeconds]) : "";
    let imageWebpFilePromise = imageCacheKey === "" ? null : imageWebpFilePromises.get(imageCacheKey);
    if (imageWebpFilePromise) { imageWebpFilePromises.delete(imageCacheKey); imageWebpFilePromises.set(imageCacheKey, imageWebpFilePromise); }
    else {
        while (imageWebpFilePromises.size >= 8) { imageWebpFilePromises.delete(imageWebpFilePromises.keys().next().value); }
        imageWebpFilePromise = (async () => {
            let selectedImageSource = imageSource;
            if (typeof imageSource === "string" && !isRawSvgSource(imageSource)) {
                const originalImageUrl = new URL(imageSource, location.href); const localUploadsUrl = new URL(uploadsUrl(), location.href); const localUploadsPath = localUploadsUrl.pathname.replace(/\/$/, "");
                if (originalImageUrl.host === localUploadsUrl.host && (originalImageUrl.pathname === localUploadsPath || originalImageUrl.pathname.startsWith(localUploadsPath + "/"))) { try { selectedImageSource = await fetchEndpoint("/wp-json/neo/image-converter-source-neo-alt", { method: "GET", query: { "image-url": originalImageUrl.toString(), "max-size": maxSize, "min-short-side": sourceMinShortSide }, timeout: sourceTimeoutSeconds }).then(extractJson).then(response => response.imageUrl || imageSource); } catch (error) { neoWarn("Image converter source lookup failed; using original image:", error); } }
            }

            const renderImageSource = async (renderSource) => {
                let imageUrl = ""; let objectUrl = ""; let imageNode = null; let canvasNode = null; const abortController = new AbortController(); let rejectTimeout;
                const timeoutPromise = new Promise((resolve, reject) => rejectTimeout = reject); const timeoutId = setTimeout(() => { abortController.abort(); rejectTimeout(new Error("Image rendering timed out.")); }, timeoutSeconds * 1000);
                try {
                    let svgContent = ""; const isRenderedSvg = isSvgSource(renderSource);
                         if (renderSource instanceof Blob) { if (isRenderedSvg) { svgContent = await renderSource.text(); } objectUrl = URL.createObjectURL(renderSource); imageUrl = objectUrl; }
                    else if (isRawSvgSource(renderSource)) { svgContent = renderSource; objectUrl = URL.createObjectURL(new Blob([renderSource], { type: "image/svg+xml" })); imageUrl = objectUrl; }
                    else if (typeof renderSource === "string" && renderSource !== "") { imageUrl = renderSource; if (isRenderedSvg) { try { svgContent = await fetch(renderSource, { cache: "default", signal: abortController.signal }).then(response => response.text()); } catch (error) { if (abortController.signal.aborted) { throw error; } neoWarn(neo__("The SVG source could not be inspected before rendering. Rendering continues without the inspection.", "Die SVG-Quelle konnte vor dem Rendern nicht geprüft werden. Das Rendern wird ohne die Prüfung fortgesetzt.")); } } }
                    else { throw new Error("Image source is missing."); }
                    if (/(?:<(?:image|use|feImage)\b[^>]*(?:href|xlink:href)\s*=\s*["'](?!#|data:|blob:)[^"']+|url\(\s*["']?(?!#|data:|blob:)[^)]+)/i.test(svgContent)) { neoWarn(neo__("The SVG uses external resources. Resources that cannot be loaded are omitted from the AI preview.", "Das SVG verwendet externe Ressourcen. Nicht ladbare Ressourcen fehlen in der AI-Vorschau.")); }
                    imageNode = new Image();
                    if (imageUrl.startsWith("http") && new URL(imageUrl, location.href).origin !== location.origin) { imageNode.crossOrigin = "anonymous"; }
                    imageNode.src = imageUrl;
                    await Promise.race([imageNode.decode(), timeoutPromise]);
                    const scale = Math.min(maxSize / Math.max(imageNode.naturalWidth, imageNode.naturalHeight), Math.max(1, resolvedMinSize / Math.max(imageNode.naturalWidth, imageNode.naturalHeight)));
                    canvasNode = document.createElement("canvas"); canvasNode.width = Math.max(1, Math.round(imageNode.naturalWidth * scale)); canvasNode.height = Math.max(1, Math.round(imageNode.naturalHeight * scale));
                    canvasNode.getContext("2d").drawImage(imageNode, 0, 0, canvasNode.width, canvasNode.height);
                    let pixelBlob;
                    for (const quality of [0.6, 0.4, 0.25]) { pixelBlob = await Promise.race([new Promise(resolve => canvasNode.toBlob(resolve, "image/webp", quality)), timeoutPromise]); if (pixelBlob.type !== "image/webp") { throw new Error("The browser could not create a WebP image."); } if (pixelBlob.size <= targetBytes) { break; } }
                    return new File([pixelBlob], fileName, { type: "image/webp", lastModified: Date.now() });
                } finally {
                    clearTimeout(timeoutId); abortController.abort(); if (objectUrl !== "") { URL.revokeObjectURL(objectUrl); } if (imageNode) { imageNode.src = ""; } if (canvasNode) { canvasNode.width = 1; canvasNode.height = 1; }
                }
            };
            try { return await renderImageSource(selectedImageSource); } catch (error) { if (selectedImageSource === imageSource) { throw error; } neoWarn("Image converter selected source failed; retrying original image:", error); return await renderImageSource(imageSource); }
        })();
        if (imageCacheKey !== "") { imageWebpFilePromises.set(imageCacheKey, imageWebpFilePromise); }
    }
    try { return await imageWebpFilePromise; } catch (error) { if (imageCacheKey !== "" && imageWebpFilePromises.get(imageCacheKey) === imageWebpFilePromise) { imageWebpFilePromises.delete(imageCacheKey); } throw new Error(neo__("The image could not be converted into a WebP preview.", "Das Bild konnte nicht in eine WebP-Vorschau umgewandelt werden.") + " " + (error?.message || "")); }
}

export async function videoFrameToWebpFile(videoSource, { fileName = "video-frame.webp", maxSize = 1920, targetBytes = 1.5 * 1024 * 1024, timeoutSeconds = 45 } = {}) {
    const videoNode = document.createElement("video"); const canvasNode = document.createElement("canvas"); let rejectTimeout;
    const timeoutPromise = new Promise((resolve, reject) => rejectTimeout = reject); const timeoutId = setTimeout(() => rejectTimeout(new Error("Video frame rendering timed out.")), timeoutSeconds * 1000);
    const waitForEvent = eventName => new Promise((resolve, reject) => { videoNode.addEventListener(eventName, resolve, { once: true }); videoNode.addEventListener("error", () => { const error = new Error("The video could not be loaded."); if ([MediaError.MEDIA_ERR_DECODE, MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED].includes(videoNode.error?.code)) { error.name = "NotSupportedError"; } reject(error); }, { once: true }); });
    try {
        videoNode.preload = "auto"; videoNode.muted = true; videoNode.playsInline = true;
        if (new URL(videoSource, location.href).origin !== location.origin) { videoNode.crossOrigin = "anonymous"; }
        const metadataLoadedPromise = waitForEvent("loadedmetadata"); videoNode.src = videoSource; videoNode.load(); await Promise.race([metadataLoadedPromise, timeoutPromise]);
        if (!(videoNode.videoWidth > 0 && videoNode.videoHeight > 0)) { throw new Error("The video has no renderable frame dimensions."); }
        if (videoNode.duration > 10) { const seekedPromise = waitForEvent("seeked"); videoNode.currentTime = 10; await Promise.race([seekedPromise, timeoutPromise]); }
        else if (videoNode.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) { await Promise.race([waitForEvent("loadeddata"), timeoutPromise]); }
        const scale = Math.min(1, maxSize / Math.max(videoNode.videoWidth, videoNode.videoHeight)); canvasNode.width = Math.max(1, Math.round(videoNode.videoWidth * scale)); canvasNode.height = Math.max(1, Math.round(videoNode.videoHeight * scale));
        canvasNode.getContext("2d").drawImage(videoNode, 0, 0, canvasNode.width, canvasNode.height);
        let frameBlob;
        for (const quality of [0.6, 0.4, 0.25]) { frameBlob = await Promise.race([new Promise(resolve => canvasNode.toBlob(resolve, "image/webp", quality)), timeoutPromise]); if (frameBlob?.type !== "image/webp") { throw new Error("The browser could not create a WebP video frame."); } if (frameBlob.size <= targetBytes) { break; } }
        return new File([frameBlob], fileName, { type: "image/webp", lastModified: Date.now() });
    } finally {
        clearTimeout(timeoutId); videoNode.pause(); videoNode.removeAttribute("src"); videoNode.load(); canvasNode.width = 1; canvasNode.height = 1;
    }
}
