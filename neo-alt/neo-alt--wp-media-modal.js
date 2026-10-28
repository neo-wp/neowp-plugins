import { observeOnce } from "./_global--observer.js";
import { integrateWpAltTextField } from "./neo-alt--wp-alt-text-field.js";

observeOnce(".media-modal-content .setting[data-setting=\"alt\"] textarea, .media-modal-content textarea[data-setting=\"alt\"], .media-frame .setting[data-setting=\"alt\"] textarea, .media-frame textarea[data-setting=\"alt\"]", (inputNode) => {
    const getFieldScope = () => inputNode.closest(".attachment-details, .media-sidebar, .embed-media-settings, .media-embed");
    const getImageUrl = () => { const frameState = window.wp?.media?.frame?.state(); const attachmentUrl = frameState?.get("selection")?.single()?.get("url") || frameState?.image?.attachment?.get("url") || frameState?.image?.get("url"); const previewImageNode = getFieldScope()?.querySelector("img.details-image, .thumbnail img, img"); return attachmentUrl || previewImageNode?.currentSrc || previewImageNode?.src || ""; };
    const getImageTitle = () => getFieldScope()?.querySelector("[data-setting=\"title\"] input")?.value || null;
    integrateWpAltTextField({ inputNode, getImageUrl, getImageTitle });
});
