import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { reloadPage } from "./_global-reload-page.js";
import { openReplaceDialog } from "./neo-replace--dialog.js";

while (!window.wp) { await new Promise(resolve => setTimeout(resolve, 100)); }
const originalRender = window.wp.media?.view?.Attachment?.Details?.TwoColumn?.prototype?.render;
if (originalRender) {
    window.wp.media.view.Attachment.Details.TwoColumn.prototype.render = function () {
        const res = originalRender.apply(this, arguments);
        const lastSetting = this.$el.find(".setting").last();
        if (!(lastSetting.length > 0)) { throw new Error("Could not find last .setting element"); }
        lastSetting.after(
            `<div class="setting" data-setting="neo-replace">
                <label for="attachment-details-two-column-neo-replace" class="name">neoReplace:</label>
                <span class="neo-replace--container" style="box-sizing: border-box; float: right; width: 65%; min-width: 0; margin: 1px; display: flex;">
                    <button id="attachment-details-two-column-neo-replace" type="button" class="button button-small neo-replace--media-library-grid-inline-replace-button">
                        <img alt="" src="${pluginUrl()}/img/_global--mini-icon.svg" style="height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.2em;">
                        ${neo__("neoReplace", "neoReplace")}
                    </button>
                </span>
            </div>`
        );

        this.$el.find(".neo-replace--media-library-grid-inline-replace-button").on("click", async (evt) => {
            evt.preventDefault();
            let didReplace = false;
            await openReplaceDialog({ imgUrl: this.model.get("url"), onUpdateCallback: () => { didReplace = true; } });
            if (didReplace) { reloadPage(); }
        });
        return res;
    };
}
