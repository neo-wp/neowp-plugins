import { neo__ } from "./_global--translation.js";
import { reloadPage } from "./_global-reload-page.js";
import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { observeOnce } from "./_global--observer.js";

let wasSaved = false;

observeOnce('a.page-title-action[href*="media-new.php"]:not(.neo-draw--pb-media-library-create-button)', async (addNewMediaButton) => {
    const newImageEditor = new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))()
        .on("save", () => {
            wasSaved = true;
        })

        .on("close", () => {
            if (!wasSaved) { return; }
            wasSaved = false;

            window.scrollTo({ top: 0, behavior: "smooth" });

            setTimeout(() => {
                reloadPage(location.origin + location.pathname);
            }, window.scrollY / 2);
        });
    const button = new DomNodeHelper(`<a href="#" class="page-title-action neo-draw--pb-media-library-create-button"><img class="neo-draw--button-logo" alt="" src="${pluginUrl()}/img/_global--mini-icon.svg" style="height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.2em;">${neo__("Create neoDraw Image", "neoDraw Bild erstellen")}</a>`)
        .on("click", () => newImageEditor.open())
        .getNode();
    addNewMediaButton.after(button);
    if ((await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "interfaceConsumeNeoDrawCreateQueryParamSuppressErrorPopup20260811"))()) { newImageEditor.open(); }
});
