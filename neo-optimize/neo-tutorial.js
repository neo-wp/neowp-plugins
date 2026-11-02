import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { observeOnce } from "./_global--observer.js";
import { currentUserCanNeoTutorialHideArrow } from "./_global--permissions-capabilities.js";
import { neo__ } from "./_global--translation.js";

export function interfaceShowTutorialArrowSuppressErrorPopup20260410(domSelector, arrowDirection = "top", arrowLabel = "new", highlightElementInsteadOfArrow = false) {
    if (!currentUserCanNeoTutorialHideArrow()) { return; }
    const isTakingScreenshot = jsVar("neoTutorialIsDemoScreenshot");
    interfaceShowTutorialArrowSuppressErrorPopup20260410.suppressedSelectors ??= jsVar("neoTutorialArrowHiddenSelectors");
    if (interfaceShowTutorialArrowSuppressErrorPopup20260410.suppressedSelectors.includes(domSelector)) { return; }
    observeOnce(domSelector, async (node) => {
        if (highlightElementInsteadOfArrow) {
            while (document.body.contains(node)) { const nodeStyle = getComputedStyle(node); const rect = node.getBoundingClientRect(); if (nodeStyle.display !== "none" && nodeStyle.visibility !== "hidden" && node.getClientRects().length > 0 && rect.bottom > 0 && rect.top < window.innerHeight && rect.right > 0 && rect.left < window.innerWidth) { break; } await new Promise(requestAnimationFrame); }
            if (!document.body.contains(node)) { return; }
            if (!isTakingScreenshot) { interfaceShowTutorialArrowSuppressErrorPopup20260410.suppressedSelectors = [...interfaceShowTutorialArrowSuppressErrorPopup20260410.suppressedSelectors, domSelector]; fetchEndpoint("/wp-json/neo/tutorial-hide-arrow", { method: "POST", body: { "dom-selector": domSelector } }); }
            node.animate([{ scale: "1" }, { scale: "1.1" }, { scale: "1" }], { duration: 550, easing: "ease-in-out", iterations: 2 });
            return;
        }

        const arrowLabelText = arrowLabel === "click" ? neo__("CLICK", "KLICKEN") : arrowLabel === "preview" ? neo__("PREVIEW HERE", "VORSCHAU HIER") : arrowLabel === "new-plugins" ? neo__("New Plugins", "Neue Plugins") : neo__("NEW", "NEU");
        let arrowZIndex = 1;
        for (let currentNode = node; currentNode; currentNode = currentNode.parentElement) {
            const currentZIndex = parseInt(getComputedStyle(currentNode).zIndex, 10);
            if (Number.isFinite(currentZIndex) && currentZIndex >= arrowZIndex) { arrowZIndex = currentZIndex + 1; }
        }
        const arrowNodeConfig = (new Map([
            ["right", { flexDirection: "row", gap: "4px", html: `←<span style="font-size: 14px;">${arrowLabelText}</span>` }],
            ["left", { flexDirection: "row", gap: "4px", html: `<span style="font-size: 14px;">${arrowLabelText}</span>→` }],
            ["bottom", { flexDirection: "column", gap: "2px", html: `↑<span style="font-size: 14px;">${arrowLabelText}</span>` }],
            ["top", { flexDirection: "column", gap: "4px", html: `<span style="font-size: 14px;">${arrowLabelText}</span>↓` }],
        ])).get(arrowDirection) ?? { flexDirection: "column", gap: "4px", html: `<span style="font-size: 14px;">${arrowLabelText}</span>↓` };
        const arrowNodeHtml = `<div class="neo-tutorial-arrow-neo-optimize" data-neo-tutorial--for-selector="${domSelector}" style="${Object.entries({
            position: "fixed", top: "0", left: "0", display: "flex", "flex-direction": arrowNodeConfig.flexDirection, gap: arrowNodeConfig.gap, "align-items": "center",
            color: "#00ff00", filter: "drop-shadow(0 0 1px #000000) drop-shadow(0 0 1px #000000)", "font-size": "24px", "font-weight": "bold", "z-index": arrowZIndex, "pointer-events": "none",
        }).map(([key, value]) => `${key}: ${value};`).join(" ")}">${arrowNodeConfig.html}</div>`;
        const arrowNode = new DomNodeHelper(arrowNodeHtml).getNode();
        document.body.appendChild(arrowNode);
        node.addEventListener("pointerenter", async () => {
            if (isTakingScreenshot) { return; }
            if (interfaceShowTutorialArrowSuppressErrorPopup20260410.suppressedSelectors.includes(domSelector)) { return; }
            interfaceShowTutorialArrowSuppressErrorPopup20260410.suppressedSelectors = [...interfaceShowTutorialArrowSuppressErrorPopup20260410.suppressedSelectors, domSelector];
            fetchEndpoint("/wp-json/neo/tutorial-hide-arrow", { method: "POST", body: { "dom-selector": domSelector } });
        });
        let arrowWidth = arrowNode.offsetWidth, arrowHeight = arrowNode.offsetHeight;
        if (arrowDirection === "right" || arrowDirection === "left") {
            const targetRect = node.getBoundingClientRect();
            if ((arrowDirection === "right" && targetRect.right + arrowWidth + 8 > window.innerWidth) || (arrowDirection === "left" && targetRect.left - arrowWidth - 8 < 0)) { arrowDirection = "top"; arrowNode.style.flexDirection = "column"; arrowNode.style.gap = "4px"; arrowNode.innerHTML = `<span style="font-size: 14px;">${arrowLabelText}</span>↓`; arrowWidth = arrowNode.offsetWidth; arrowHeight = arrowNode.offsetHeight; }
        }
        const mediaToolbar = document.querySelector(".media-toolbar.wp-filter");
        const mediaToolbarZIndex = mediaToolbar ? parseInt(getComputedStyle(mediaToolbar).zIndex, 10) || 0 : 0;

        (async () => {
            while (true) {
                if (!document.body.contains(node)) { arrowNode.remove(); return; }
                const nodeStyle = getComputedStyle(node);
                if (nodeStyle.display === "none" || nodeStyle.visibility === "hidden" || node.getClientRects().length === 0) { arrowNode.style.visibility = "hidden"; await new Promise(requestAnimationFrame); continue; }
                arrowNode.style.visibility = "visible";
                const rect = node.getBoundingClientRect();
                if (arrowDirection === "right") {
                    arrowNode.style.transform = `translate(${rect.right + 8}px, ${rect.top + rect.height / 2 - arrowHeight / 2}px)`;
                } else if (arrowDirection === "left") {
                    arrowNode.style.transform = `translate(${rect.left - arrowWidth - 8}px, ${rect.top + rect.height / 2 - arrowHeight / 2}px)`;
                } else if (arrowDirection === "bottom") {
                    arrowNode.style.transform = `translate(${rect.left + rect.width / 2 - arrowWidth / 2}px, ${rect.bottom + 4}px)`;
                } else {
                    arrowNode.style.transform = `translate(${rect.left + rect.width / 2 - arrowWidth / 2}px, ${rect.top - arrowHeight}px)`;
                }

                const arrowRect = arrowNode.getBoundingClientRect();
                const mediaToolbarRect = mediaToolbar?.getBoundingClientRect();
                const overlapsMediaToolbar = mediaToolbarRect && arrowRect.left < mediaToolbarRect.right && arrowRect.right > mediaToolbarRect.left && arrowRect.top < mediaToolbarRect.bottom && arrowRect.bottom > mediaToolbarRect.top;
                arrowNode.style.zIndex = overlapsMediaToolbar ? Math.max(arrowZIndex, mediaToolbarZIndex + 1) : arrowZIndex;
                await new Promise(requestAnimationFrame);
            }
        })();
        if (isTakingScreenshot) { return; }

        while (arrowNode.isConnected && !interfaceShowTutorialArrowSuppressErrorPopup20260410.suppressedSelectors.includes(domSelector)) { await new Promise(requestAnimationFrame); }
        if (!arrowNode.isConnected) { return; }

        await new Promise(resolve => setTimeout(resolve, 1000));
        arrowNode.style.transition = "opacity 1000ms linear";
        arrowNode.style.opacity = "0";
    });
}
