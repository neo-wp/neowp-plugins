import { domLoaded } from "./_global--observer.js";

import { neoLoadInterfaceFunc } from "./_global--interface.js";

import { isInterfaceFunctionErrorMessage } from "./_global--interface.js";

async function transformImgIntoSvg(imgNode) {
    transformImgIntoSvg.svgIdsByImgNode ??= new Map();
    const removeExistingSvgForImgNode = () => {
        const svgNodeId = transformImgIntoSvg.svgIdsByImgNode.get(imgNode);
        if (!svgNodeId) { return; }
        document.querySelector(`[data-neo-draw--svg-id="${svgNodeId}"]`)?.remove();
        document.querySelector(`style[data-neo-draw--computed-base-styles-for-svg-id=${svgNodeId}]`)?.remove();
        transformImgIntoSvg.svgIdsByImgNode.delete(imgNode);
    };
    if (!imgNode.getAttribute("src")) { removeExistingSvgForImgNode(); return; }
    const isNeoDraw = imgNode.getAttribute("src").includes("neo-draw=true");
    if (!isNeoDraw) { removeExistingSvgForImgNode(); return; }
    const isLinked = imgNode.getAttribute("src").includes("neo-draw--linked=true");
    let isAnimated = false;
    try { isAnimated = await (await neoLoadInterfaceFunc("neo-animate", "neo-animate--frontend-interface.js", "interfaceIsNeoDrawImageAnimatedSuppressErrorPopup20260607"))(imgNode); } catch (e) { isAnimated = false; }
    if (!(isLinked || isAnimated)) { removeExistingSvgForImgNode(); return; }
    transformImgIntoSvg.svgIndexCounterForIds ??= 0;
    let svgNodeId;
    if (transformImgIntoSvg.svgIdsByImgNode.has(imgNode)) {
        svgNodeId = transformImgIntoSvg.svgIdsByImgNode.get(imgNode);
        document.querySelector(`[data-neo-draw--svg-id="${svgNodeId}"]`)?.remove();
    } else {
        svgNodeId = `neo-draw--svg-${transformImgIntoSvg.svgIndexCounterForIds}`;
        transformImgIntoSvg.svgIndexCounterForIds += 1;
        transformImgIntoSvg.svgIdsByImgNode.set(imgNode, svgNodeId);
    }
    await new Promise((resolve, reject) => {
        if (imgNode.complete) {
            resolve();
        } else {
            const resolveAndRemoveOldListeners = () => { resolve(); imgNode.removeEventListener("load", resolveAndRemoveOldListeners); imgNode.removeEventListener("error", rejectAndRemoveOldListeners); };
            const rejectAndRemoveOldListeners = () => { reject(); imgNode.removeEventListener("load", resolveAndRemoveOldListeners); imgNode.removeEventListener("error", rejectAndRemoveOldListeners); };
            imgNode.addEventListener("load", resolveAndRemoveOldListeners);
            imgNode.addEventListener("error", rejectAndRemoveOldListeners);
        }
    });
    const { fitProtocolToFetchImgUrl } = await import("./_global--url-helper.js");
    const resp = await fetch(fitProtocolToFetchImgUrl(imgNode.src));
    if (!resp.ok) { throw new Error("The image " + imgNode.src + " could not be loaded. Error code: " + resp.status); }
    const svgContent = await (resp).text();
    if (!svgContent.includes("<svg")) {
        throw new Error("The image " + imgNode.src + "is not a valid SVG image. It does not contain an <svg> tag.");
    }
    const { DomNodeHelper } = await import("./_global--dom-node-helper.js");
    const svgNode = new DomNodeHelper(svgContent).withClass("neo-draw--unpacked-svg").getNode();
    svgNode.setAttribute("data-neo-draw--svg-id", svgNodeId);
    const allIds = [
        ...[...svgNode.querySelectorAll("[id]")].map(node => node.getAttribute("id")),
        ...[...svgNode.querySelectorAll("[data-neo-animate--id]")].map(node => node.getAttribute("data-neo-animate--id")),
    ].filter((value, index, self) => self.indexOf(value) === index);
    let svgInnerHtml = svgNode.innerHTML;
    for (const id of allIds) {
        if (id.length < 10) {
            const { neoWarn } = await import("./_global--log.js");
            neoWarn(`neoDraw: The ID "${id}" in the SVG "${svgNodeId}" (src "${imgNode.src}") is very short. It is probably not unique. Please make sure that all IDs in the SVG are unique.`);
            continue;
        }
        svgInnerHtml = svgInnerHtml.replaceAll(id, `${svgNodeId}-${id}-unique`.replaceAll("-", "_"));
    }
    svgNode.innerHTML = svgInnerHtml;
    function copyComputedBaseStylesFromImgNodeToSvgNode(imgNode) {
        document.querySelector(`style[data-neo-draw--computed-base-styles-for-svg-id=${svgNodeId}]`)?.remove();
        const inlineStyles = imgNode.getAttribute("style");
        imgNode.removeAttribute("style");
        const computedStyles = window.getComputedStyle(imgNode);
        let cssText = "";
        const computedStylesLength = computedStyles.length;
        for (let i = 0; i < computedStylesLength; i++) {
            const styleName = computedStyles[i];
            if (styleName === "visibility") continue;
            cssText += `${styleName}: ${computedStyles.getPropertyValue(styleName)}; `;
        }
        imgNode.style.setProperty("opacity", "0", "important");
        imgNode.classList.add("neo-animate--suppress-flash-prevention");
        cssText += `visibility: ${computedStyles.getPropertyValue("visibility")}; `;
        const baseStylesTag = document.createElement("style");
        baseStylesTag.setAttribute("data-neo-draw--computed-base-styles-for-svg-id", svgNodeId);
        baseStylesTag.innerHTML =
`svg[data-neo-draw--svg-id="${svgNodeId}"]:not(#more-specificity) { ${cssText} }
svg[data-neo-draw--svg-id="${svgNodeId}"]:not(#more-specificity) + img { display: none !important; }`;
        imgNode.parentNode.insertBefore(baseStylesTag, imgNode.nextSibling);
        imgNode.classList.remove("neo-animate--suppress-flash-prevention");
        if (inlineStyles === null) imgNode.removeAttribute("style");
        else { imgNode.setAttribute("style", inlineStyles); }
    }
    copyComputedBaseStylesFromImgNodeToSvgNode(imgNode);
    new MutationObserver(async (mutations) => {
        const cssLoadPromises = [];
        for (const mutation of mutations) {
            for (const changedNode of [mutation.target, ...mutation.addedNodes]) {
                if (changedNode.tagName?.toLowerCase() === "link") {
                    cssLoadPromises.push(new Promise(resolve => {
                        if (changedNode.sheet) { resolve(); }
                        else {
                            changedNode.addEventListener("load", resolve);
                            changedNode.addEventListener("error", resolve);
                        }
                    }));
                } else if (changedNode.tagName?.toLowerCase() === "style") {
                    let isNeoAnimateAnimationStyleNode = false;
                    try { isNeoAnimateAnimationStyleNode = await (await neoLoadInterfaceFunc("neo-animate", "neo-animate--frontend-interface.js", "interfaceIsNeoAnimateAnimationStyleNodeSuppressErrorPopup20260609"))(changedNode); } catch (e) { if (!isInterfaceFunctionErrorMessage(e.message)) { throw e; } }
                    if (isNeoAnimateAnimationStyleNode) { continue; }
                    if (changedNode.getAttribute("data-neo-draw--computed-base-styles-for-svg-id")) { continue; }
                    cssLoadPromises.push(Promise.resolve());
                }
            }
        }
        await Promise.allSettled(cssLoadPromises);
        if (cssLoadPromises.length > 0) {
            copyComputedBaseStylesFromImgNodeToSvgNode(imgNode);
        }
    }).observe(document.querySelector("html"), { childList: true, subtree: true });
    function transferAttributesFromImgToSvg(imgSourceNode, svgTargetNode) {
        const neodrawClasses = [...svgTargetNode.classList].filter(c => c.startsWith("neo-"));
        for (const attr of imgSourceNode.attributes) {
            if (attr.value === svgTargetNode.getAttribute(attr.name)) { continue; }
            svgTargetNode.setAttribute(attr.name, imgSourceNode.getAttribute(attr.name));
        }
        neodrawClasses.forEach((neodrawClass) => { svgTargetNode.classList.add(neodrawClass); });
    }
    new MutationObserver(() => { transferAttributesFromImgToSvg(imgNode, svgNode); }).observe(imgNode, { attributes: true });
    transferAttributesFromImgToSvg(imgNode, svgNode);
    imgNode.parentNode.insertBefore(svgNode, imgNode);
    if (isAnimated) {
        try { await (await neoLoadInterfaceFunc("neo-animate", "neo-animate--frontend-interface.js", "interfaceStartNeoDrawSvgAnimationSuppressErrorPopup20260607"))({ svgNode }); } catch (e) { if (!isInterfaceFunctionErrorMessage(e.message)) { throw e; } }
    }
}
domLoaded(async () => {
    await Promise.allSettled([...document.querySelectorAll("img")].map(node => {
        return transformImgIntoSvg(node);
    }));
    new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === "attributes" && mutation.attributeName === "src") {
                if (mutation.target.tagName.toLowerCase() !== "img") { continue; }
                transformImgIntoSvg(mutation.target);
            }
            for (const node of mutation.addedNodes) {
                if (node.tagName?.toLowerCase() !== "img") { continue; }
                transformImgIntoSvg(node);
            }
        }
    }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ["src"] });
});
