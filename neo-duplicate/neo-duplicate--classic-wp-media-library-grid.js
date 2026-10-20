import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { reloadPage } from "./_global-reload-page.js";
import { duplicateMediaFileData } from "./neo-duplicate.js";
import Swal from "./_global-sweetalert2.js";

while (!window.wp) { await new Promise(resolve => setTimeout(resolve, 100)); }
const originalRender = window.wp.media?.view?.Attachment?.Details?.TwoColumn?.prototype?.render;
if (originalRender) {
    window.wp.media.view.Attachment.Details.TwoColumn.prototype.render = function () {
        const res = originalRender.apply(this, arguments);
        const lastSetting = this.$el.find(".setting").last();
        if (!(lastSetting.length > 0)) { throw new Error("Could not find last .setting element"); }
        lastSetting.after(
            `<div class="setting" data-setting="neo-duplicate">
                <label for="attachment-details-two-column-neo-duplicate" class="name">neoDuplicate:</label>
                <span class="neo-duplicate--container" style="box-sizing: border-box; float: right; width: 65%; min-width: 0; margin: 1px; display: flex;">
                    <button id="attachment-details-two-column-neo-duplicate" type="button" class="button button-small neo-duplicate--media-library-grid-inline-duplicate-button">
                        <img alt="" src="${pluginUrl()}/img/_global--mini-icon.svg" style="height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.2em;">
                        ${neo__("neoDuplicate", "neoDuplizieren")}
                    </button>
                </span>
            </div>`
        );

        this.$el.find(".neo-duplicate--media-library-grid-inline-duplicate-button").on("click", async (evt) => {
            evt.preventDefault();
            const buttonNode = evt.currentTarget; const oldButtonHtml = buttonNode.innerHTML;
            buttonNode.disabled = true; buttonNode.textContent = neo__("Duplicating...", "Dupliziere...");
            try {
                const data = await duplicateMediaFileData(this.model.get("url"));
                const targetUrl = new URL(location.href); targetUrl.searchParams.set("item", data.postId); targetUrl.hash = "";
                reloadPage(targetUrl.toString());
            } catch (err) {
                buttonNode.disabled = false; buttonNode.innerHTML = oldButtonHtml;
                await Swal.fire({ icon: "error", title: "Duplicate failed", text: err.message });
            }
        });
        return res;
    };
}
