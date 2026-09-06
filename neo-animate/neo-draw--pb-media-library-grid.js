import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { observeOnce } from "./_global--observer.js";
import { addCacheBust } from "./_global--url-helper.js";
import { InterfaceEditorDialog20260826 } from "./neo-draw--editor-dialog.js";
import { reloadPage } from "./_global-reload-page.js";

function openNeoDrawGridEditor(imgUrl) {
    let savedImgUrl = null; let savedImgId = null;
    new InterfaceEditorDialog20260826().imgUrl(imgUrl).on("save", ({ imgUrl: newImgUrl, imgId }) => { savedImgUrl = newImgUrl; savedImgId = imgId; if (savedImgUrl === imgUrl) { const imgUrlWithoutQuery = savedImgUrl.split("?")[0]; const cachebustedImgUrl = addCacheBust(imgUrlWithoutQuery); for (const imgNode of document.querySelectorAll(".attachments-browser .attachment-preview img, .attachment-details .details-image")) { const currentImgUrlWithoutQuery = (imgNode.getAttribute("src") ?? "").split("?")[0]; if (currentImgUrlWithoutQuery !== imgUrlWithoutQuery) { continue; } imgNode.setAttribute("src", cachebustedImgUrl); } } }).on("close", () => {
        if (!savedImgUrl || savedImgUrl === imgUrl) { return; }
        if (!savedImgId) { reloadPage(); return; }
        const url = new URL(location.href); url.searchParams.set("item", savedImgId); reloadPage(url.toString());
    }).open();
}
while (!window.wp) { await new Promise(resolve => setTimeout(resolve, 100)); }
const originalRender = window.wp.media?.view?.Attachment?.Details?.TwoColumn?.prototype?.render;
if (originalRender) {
    window.wp.media.view.Attachment.Details.TwoColumn.prototype.render = function () {
        const res = originalRender.apply(this, arguments);
        if (!this.model.get("is_neodraw")) { return res; }
        if (this.$el.find(".neo-draw--media-library-grid-inline-edit-button").length > 0) { return res; }
        const lastSetting = this.$el.find(".setting").last();
        if (!(lastSetting.length > 0)) { throw new Error("Could not find last .setting element"); }
        lastSetting.after(
            `<div class="setting" data-setting="neo-draw">
                <label for="attachment-details-two-column-neo-draw" class="name">neoDraw:</label>
                <span class="neo-draw--container" style="box-sizing: border-box; float: right; width: 65%; min-width: 0; margin: 1px; display: flex;">
                    <button id="attachment-details-two-column-neo-draw" type="button" class="button button-small neo-draw--media-library-grid-inline-edit-button">
                        <img alt="" src="${pluginUrl()}/img/neo-draw--edit-icon.svg" style="height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.2em;">
                        ${neo__("Edit with neoDraw", "Mit neoDraw bearbeiten")}
                    </button>
                </span>
            </div>`
        );
        this.$el.find(".neo-draw--media-library-grid-inline-edit-button").on("click", async (evt) => {
            evt.preventDefault();
            openNeoDrawGridEditor(this.model.get("url"));
        });
        return res;
    };
}
if (!document.querySelector("style#neo-draw--media-library-grid-hover-edit-style")) {
    const styleNode = document.createElement("style"); styleNode.id = "neo-draw--media-library-grid-hover-edit-style";
    styleNode.textContent = `.attachments-browser .attachments .attachment .neo-draw--media-library-grid-hover-edit-button{position:absolute;top:6px;right:6px;z-index:20;width:28px;height:28px;min-height:28px;padding:0;border:1px solid #8c8f94;border-radius:4px;background:#ffffff url("${pluginUrl()}/img/neo-draw--edit-icon.svg") center/17px 17px no-repeat;box-shadow:0 1px 2px rgba(0,0,0,.22);opacity:0;transition:opacity .08s ease,transform .08s ease}.attachments-browser .attachments .attachment.neo-draw--media-library-grid-hover-editable:hover .neo-draw--media-library-grid-hover-edit-button,.attachments-browser .attachments .attachment .neo-draw--media-library-grid-hover-edit-button:focus{opacity:1}.attachments-browser .attachments .attachment .neo-draw--media-library-grid-hover-edit-button:hover{transform:scale(1.08);background-color:#f6f7f7}`;
    document.head.appendChild(styleNode);
}
observeOnce(".attachments-browser .attachments .attachment[data-id]:not(.neo-draw--media-library-grid-hover-edit-processed)", async (attachmentNode) => {
    attachmentNode.classList.add("neo-draw--media-library-grid-hover-edit-processed");
    const attachmentId = parseInt(attachmentNode.getAttribute("data-id"), 10);
    if (!Number.isInteger(attachmentId) || attachmentId < 1) { return; }
    const attachment = wp.media.attachment(attachmentId);
    const isNeoDrawByDom = Boolean(attachmentNode.querySelector("img[data-neo-draw--is-neodraw=\"true\"]"));
    try { if (attachment.get("is_neodraw") == null || !attachment.get("url")) { await attachment.fetch(); } } catch (error) { if (!isNeoDrawByDom) { return; } }
    if (!attachment.get("is_neodraw") && !isNeoDrawByDom) { return; }
    const imgUrl = attachment.get("url") || attachmentNode.querySelector("img")?.getAttribute("src")?.split("?")[0];
    if (!imgUrl) { return; }
    const previewNode = attachmentNode.querySelector(".attachment-preview");
    if (!previewNode || previewNode.querySelector(".neo-draw--media-library-grid-hover-edit-button")) { return; }
    attachmentNode.classList.add("neo-draw--media-library-grid-hover-editable");
    const editButton = document.createElement("button"); editButton.type = "button"; editButton.className = "button neo-draw--media-library-grid-hover-edit-button"; editButton.title = neo__("Edit neoDraw", "neoDraw bearbeiten"); editButton.setAttribute("aria-label", neo__("Edit neoDraw", "neoDraw bearbeiten"));
    editButton.addEventListener("pointerdown", (event) => { event.preventDefault(); event.stopPropagation(); });
    editButton.addEventListener("click", (event) => { event.preventDefault(); event.stopPropagation(); openNeoDrawGridEditor(imgUrl); });
    previewNode.appendChild(editButton);
});
