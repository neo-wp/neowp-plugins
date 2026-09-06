import { observeClick, observeOnce } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";
import { pricingUrl } from "./_global-pricing-url.js";

import { reloadPage } from "./_global-reload-page.js";
import { duplicateMediaFileData } from "./neo-duplicate.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import Swal from "./_global-sweetalert2.js";

if (/^#post-\d+$/.test(location.hash)) {
    if ("scrollRestoration" in history) { history.scrollRestoration = "manual"; }
    observeOnce(location.hash, async (rowNode) => {
        await new Promise(requestAnimationFrame); await new Promise(resolve => setTimeout(resolve, 100));
        rowNode.scrollIntoView({ behavior: "smooth", block: "center" });
    });
}

function reloadMediaLibraryToDuplicate(data) {
    const targetUrl = new URL(location.href);
    if (data.useTodayAsUploadDate) {
        targetUrl.searchParams.delete("paged"); targetUrl.searchParams.delete("m");
    }
    targetUrl.hash = "post-" + data.postId;
    reloadPage(targetUrl.toString());
}
function registerBulkDuplicateAction(selectSelector, buttonSelector) {
    observeOnce(selectSelector, (selectNode) => {
        const optionValue = "neo-duplicate--bulk"; const optionNode = document.createElement("option");
        optionNode.value = optionValue; optionNode.textContent = neo__("neoDuplicate", "neoDuplicate");
        optionNode.textContent = optionNode.textContent + " 👑"; 
        selectNode.appendChild(optionNode);
        observeClick(buttonSelector, async (buttonNode, event) => {
            if (selectNode.value !== optionValue) { return; }
            event.preventDefault();
            window.open(pricingUrl(), "_blank", "noopener"); return;
        });
    });
}
registerBulkDuplicateAction("#bulk-action-selector-top", "#doaction");
registerBulkDuplicateAction("#bulk-action-selector-bottom", "#doaction2");

observeOnce(".neo-duplicate--media-library-list-inline-duplicate-button", async (buttonNode) => {
    observeClick(buttonNode, async (buttonNode, evt) => {
        evt.preventDefault();
        let data;
        try {
            data = await duplicateMediaFileData(buttonNode.getAttribute("data-neo-duplicate--img-url"));
        } catch (err) {
            await Swal.fire({ icon: "error", title: "Duplicate failed", text: err.message });
            return;
        }
        reloadMediaLibraryToDuplicate(data);
    });
    buttonNode.style.cssText = "";
    buttonNode.setAttribute("data-neo-duplicate--dialog-ready", "true");
});
(await neoLoadInterfaceFunc("neo-duplicate", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-duplicate--media-library-list-inline-duplicate-button", "top", "new", true);
if (location.hash === "#neo-duplicate--open-tutorial") { window.history.replaceState(null, "", location.pathname + location.search); }
