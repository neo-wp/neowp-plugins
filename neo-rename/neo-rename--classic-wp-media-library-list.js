import { domLoaded, observeClick, observeOnce } from "./_global--observer.js";
import { reloadPage } from "./_global-reload-page.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { openRenameDialog } from "./neo-rename--dialog.js";

observeOnce(".neo-rename--media-library-list-inline-rename-button", async (buttonNode) => {
    observeClick(buttonNode, async (buttonNode, evt) => {
        evt.preventDefault();
        let didRename = false;
        await openRenameDialog({ filterInputText: buttonNode.getAttribute("data-neo-rename--img-url"), onUpdateCallback: () => { didRename = true; } });
        if (didRename) { reloadPage(); }
    });
    buttonNode.style.cssText = "";
    buttonNode.setAttribute("data-neo-rename--dialog-ready", "true");
});
(await neoLoadInterfaceFunc("neo-rename", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-rename--media-library-list-inline-rename-button", "top", "new", true);

if (location.hash === "#neo-rename--open-tutorial") {
    window.history.replaceState(null, "", location.pathname + location.search);
    domLoaded(async () => {
        const firstRenameButton = document.querySelector(".neo-rename--media-library-list-inline-rename-button");
        if (firstRenameButton) {
            while (!firstRenameButton.hasAttribute("data-neo-rename--dialog-ready")) { await new Promise(requestAnimationFrame); }
            firstRenameButton.click();
        } else {
            await openRenameDialog({ filterInputText: "", inputMode: "find-replace" });
        }
    });
}
