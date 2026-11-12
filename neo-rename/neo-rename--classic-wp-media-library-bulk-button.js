import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { observeOnce } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { reloadPage } from "./_global-reload-page.js";
import { openRenameDialog } from "./neo-rename--dialog.js";

observeOnce('a.page-title-action[href*="media-new.php"]:not(.neo-rename--wp-media-library-bulk-button)', async (addNewMediaButton) => {
    const button = new DomNodeHelper(`<a href="#" class="page-title-action neo-rename--wp-media-library-bulk-button"><img alt="" src="${pluginUrl()}/img/_global--mini-icon.svg" style="height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.2em;">${neo__("neoRename Bulk", "neoRename Stapelverarbeitung")}</a>`)
        .on("click", async () => {
            let didRename = false;
            await openRenameDialog({ filterInputText: "", inputMode: "find-replace", onUpdateCallback: () => { didRename = true; } });
            if (didRename) { reloadPage(); }
        })
        .getNode();
    addNewMediaButton.after(button);
});
