import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { reloadPage } from "./_global-reload-page.js";
import { openRenameDialog } from "./neo-rename--dialog.js";

while (!window.wp || (window._wpMediaGridSettings && !window.wp.media?.view?.Attachment?.Details?.TwoColumn?.prototype?.render)) { await new Promise(resolve => setTimeout(resolve, 100)); }
const originalRender = window.wp.media?.view?.Attachment?.Details?.TwoColumn?.prototype?.render;
if (originalRender) {
    window.wp.media.view.Attachment.Details.TwoColumn.prototype.render = function () {
        const res = originalRender.apply(this, arguments);
        const lastSetting = this.$el.find(".setting").last();
        if (!(lastSetting.length > 0)) { throw new Error("Could not find last .setting element"); }

        lastSetting.after(
            `<div class="setting" data-setting="neo-rename">
                <label for="attachment-details-two-column-neo-rename" class="name">neoRename:</label>
                <span class="neo-rename--container" style="box-sizing: border-box; float: right; width: 65%; min-width: 0; margin: 1px; display: flex;">
                    <button id="attachment-details-two-column-neo-rename" type="button" class="button button-small neo-rename--media-library-grid-inline-rename-button">
                        <img alt="" src="${pluginUrl()}/img/_global--mini-icon.svg" style="height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.2em;">
                        ${neo__("neoRename", "neoRename")}
                    </button>
                </span>
            </div>`
        );

        this.$el.find(".neo-rename--media-library-grid-inline-rename-button").on("click", async (evt) => {
            evt.preventDefault();
            let didRename = false;
            await openRenameDialog({ filterInputText: this.model.get("url"), onUpdateCallback: () => { didRename = true; } });
            if (didRename) { reloadPage(); }
        });
        return res;
    };
}
