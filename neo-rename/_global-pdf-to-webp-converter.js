import { fitProtocolToFetchImgUrl } from "./_global--url-helper.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { neo__ } from "./_global--translation.js";

let pdfJsModulePromise = null;

export async function pdfFirstPageToWebpFile(pdfUrl, { fileName = "pdf-first-page.webp", maxSize = 1920, targetBytes = 1.5 * 1024 * 1024, timeoutSeconds = 45, shouldAbort = null } = {}) {
    pdfJsModulePromise ??= import("./_global-pdf-thirdparty/pdf.js").then(pdfJsModule => { pdfJsModule.GlobalWorkerOptions.workerSrc = pluginUrl() + "/_global-pdf-thirdparty/pdf.worker.js"; return pdfJsModule; });
    const { getDocument } = await pdfJsModulePromise; const canvasNode = document.createElement("canvas"); let pdfLoadingTask = null; let pdfDocument = null; let pdfPage = null; let renderTask = null; let rejectTimeout; let rejectAbort;
    const timeoutPromise = new Promise((resolve, reject) => rejectTimeout = reject); const timeoutId = setTimeout(() => rejectTimeout(new Error("PDF preview rendering timed out.")), timeoutSeconds * 1000);
    const abortPromise = new Promise((resolve, reject) => rejectAbort = reject); const abortIntervalId = typeof shouldAbort === "function" ? setInterval(() => { if (!shouldAbort()) { return; } const abortError = new Error("PDF preview rendering was aborted."); abortError.name = "AbortError"; rejectAbort(abortError); }, 100) : null;
    try {
        pdfLoadingTask = getDocument(fitProtocolToFetchImgUrl(pdfUrl)); pdfDocument = await Promise.race([pdfLoadingTask.promise, timeoutPromise, abortPromise]);
        pdfPage = await Promise.race([pdfDocument.getPage(1), timeoutPromise, abortPromise]);
        const baseViewport = pdfPage.getViewport({ scale: 1 }); const scale = maxSize / Math.max(baseViewport.width, baseViewport.height); const viewport = pdfPage.getViewport({ scale });
        canvasNode.width = Math.max(1, Math.round(viewport.width)); canvasNode.height = Math.max(1, Math.round(viewport.height));
        renderTask = pdfPage.render({ canvasContext: canvasNode.getContext("2d"), viewport }); await Promise.race([renderTask.promise, timeoutPromise, abortPromise]);
        let pageBlob;
        for (const quality of [0.6, 0.4, 0.25]) { pageBlob = await Promise.race([new Promise(resolve => canvasNode.toBlob(resolve, "image/webp", quality)), timeoutPromise, abortPromise]); if (pageBlob?.type !== "image/webp") { throw new Error("The browser could not create a WebP PDF preview."); } if (pageBlob.size <= targetBytes) { break; } }
        return new File([pageBlob], fileName, { type: "image/webp", lastModified: Date.now() });
    } catch (error) { if (error?.name === "AbortError") { throw error; } throw new Error(neo__("The first PDF page could not be rendered as a WebP preview.", "Die erste PDF-Seite konnte nicht als WebP-Vorschau gerendert werden.") + " " + (error?.message || "")); }
    finally { clearTimeout(timeoutId); if (abortIntervalId !== null) { clearInterval(abortIntervalId); } renderTask?.cancel(); pdfPage?.cleanup(); if (pdfDocument) { await pdfDocument.destroy(); } else { await pdfLoadingTask?.destroy(); } canvasNode.width = 1; canvasNode.height = 1; }
}
