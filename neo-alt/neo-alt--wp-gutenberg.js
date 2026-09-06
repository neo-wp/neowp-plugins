import { observeOnce } from "./_global--observer.js";
import { integrateWpAltTextField } from "./neo-alt--wp-alt-text-field.js";

observeOnce(".block-editor-block-inspector textarea.components-textarea-control__input, .wp-block-image__toolbar_content_textarea textarea.components-textarea-control__input", (inputNode) => {
    const labelText = inputNode.closest(".components-base-control")?.querySelector("label")?.textContent.trim() || "";
    const alternativeTextLabels = [window.wp.i18n.__("Alternative text"), window.wp.i18n.__("Alt text")];
    if (!alternativeTextLabels.includes(labelText)) { return; }
    const getBlockContext = () => {
        const block = window.wp.data.select("core/block-editor").getSelectedBlock();
        if (!block) { return null; }
        if (block.name === "core/media-text") { return { block, altAttribute: "mediaAlt", imageUrl: block.attributes.mediaUrl || "", mediaId: block.attributes.mediaId || 0 }; }
        if (["core/image", "core/cover"].includes(block.name)) { return { block, altAttribute: "alt", imageUrl: block.attributes.url || "", mediaId: block.attributes.id || 0 }; }
        return null;
    };
    const getMedia = () => { const blockContext = getBlockContext(); return blockContext?.mediaId ? window.wp.data.select("core").getMedia(blockContext.mediaId) : null; };
    const getImageUrl = () => getMedia()?.source_url || getBlockContext()?.imageUrl || "";
    const getImageTitle = () => getMedia()?.title?.rendered || null;
    const getSetAltText = () => { const blockContext = getBlockContext(); if (!blockContext) { return null; } return generatedAltText => window.wp.data.dispatch("core/block-editor").updateBlockAttributes(blockContext.block.clientId, { [blockContext.altAttribute]: generatedAltText }); };
    if (!getBlockContext()) { return; }
    integrateWpAltTextField({ inputNode, getImageUrl, getImageTitle, getSetAltText, keepInputInPlace: true });
});
