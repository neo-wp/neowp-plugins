import { observeAttributes, observeOnce } from "./_global--observer.js";
import { addQueryParam, fitProtocolToFetchImgUrl, getQueryParam } from "./_global--url-helper.js";

export function cachebustImageUrl(url, { queryKey, cachebustValue } = {}) {
    if (!url) { return url; }
    let origin = location.origin; let url_base = location.href; if (origin === "null" && window.parent?.location?.origin) { origin = window.parent.location.origin; url_base = window.parent.location.href; }
    try { url = new URL(url, url_base).toString(); } catch (error) { return url; }
    if (fitProtocolToFetchImgUrl(url) !== fitProtocolToFetchImgUrl(origin) && !fitProtocolToFetchImgUrl(url).startsWith(fitProtocolToFetchImgUrl(origin) + "/")) { return url; }
    if (getQueryParam(url, queryKey)) { return url; }
    return addQueryParam(url, queryKey, cachebustValue);
}

export function cachebustImageSrcset(srcset, options = {}) {
    if (!srcset) { return srcset; }
    return srcset.split(",").map((srcsetPart) => {
        const srcsetPartPieces = srcsetPart.trim().split(/\s+/);
        const srcsetUrl = srcsetPartPieces.shift();
        if (!srcsetUrl) { return srcsetPart.trim(); }
        return [cachebustImageUrl(srcsetUrl, options), ...srcsetPartPieces].join(" ");
    }).join(", ");
}

export function interfaceCachebustAttachmentImageUrls20260730({url = "", sizes = null} = {}, cachebustValue = Date.now()) {
    const options = {queryKey: "neo-image-cachebust--editor-save", cachebustValue};
    return {url: cachebustImageUrl(url, options), sizes: sizes ? Object.fromEntries(Object.entries(sizes).map(([sizeName, sizeData]) => [sizeName, {...sizeData, url: cachebustImageUrl(sizeData.url, options)}])) : sizes};
}

export function observeEditorImages(domRoot = document.documentElement, selector = "img", options = {}) {
    observeOnce(selector, (imgNode) => {
        observeAttributes(imgNode, () => {
            const cachebustedSrc    = cachebustImageUrl   (imgNode.getAttribute("src")    ?? "", options);
            const cachebustedSrcset = cachebustImageSrcset(imgNode.getAttribute("srcset") ?? "", options);
            if (cachebustedSrc    && imgNode.src    !== cachebustedSrc)    { imgNode.src    = cachebustedSrc; }
            if (cachebustedSrcset && imgNode.srcset !== cachebustedSrcset) { imgNode.srcset = cachebustedSrcset; }
        });
    }, { domRoot });
}
