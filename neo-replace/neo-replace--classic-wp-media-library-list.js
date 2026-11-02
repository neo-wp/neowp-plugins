import { domLoaded, observeClick, observeOnce } from "./_global--observer.js";
import { reloadPage } from "./_global-reload-page.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { openReplaceDialog } from "./neo-replace--dialog.js";

observeOnce(".neo-replace--media-library-list-inline-replace-button", async (buttonNode) => {
    observeClick(buttonNode, async (buttonNode, evt) => {
        evt.preventDefault();
        let didReplace = false;
        await openReplaceDialog({ imgUrl: buttonNode.getAttribute("data-neo-replace--img-url"), onUpdateCallback: () => { didReplace = true; } });
        if (didReplace) { reloadPage(); }
    });
    buttonNode.style.cssText = "";
    buttonNode.setAttribute("data-neo-replace--dialog-ready", "true");
});

(await neoLoadInterfaceFunc("neo-replace", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-replace--media-library-list-inline-replace-button", "top", "new", true);

if (location.hash === "#neo-replace--open-tutorial") {
    window.history.replaceState(null, "", location.pathname + location.search);
    domLoaded(async () => {
        const firstReplaceButton = document.querySelector(".neo-replace--media-library-list-inline-replace-button");
        if (firstReplaceButton) {
            while (!firstReplaceButton.hasAttribute("data-neo-replace--dialog-ready")) { await new Promise(requestAnimationFrame); }
            firstReplaceButton.click();
        }
    });
}
