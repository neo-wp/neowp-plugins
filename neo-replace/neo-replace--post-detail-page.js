import { observeOnce, observeClick } from "./_global--observer.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { neo__ } from "./_global--translation.js";
import { reloadPage } from "./_global-reload-page.js";
import { openReplaceDialog } from "./neo-replace--dialog.js";

observeOnce(".misc-pub-section.misc-pub-download", (downloadLinkNode) => {
    if (window.self !== window.top) { return; }

    const neoReplaceSection = document.createElement("div"); neoReplaceSection.classList.add("misc-pub-section", "misc-pub-neoreplace");
    const replaceButton = document.createElement("button"); replaceButton.className = "button button-secondary";
    replaceButton.innerHTML = `<img alt="" src="${pluginUrl()}/img/_global--mini-icon.svg" style="height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.4em;">${neo__("neoReplace", "neoReplace")}`;
    neoReplaceSection.appendChild(replaceButton);
    downloadLinkNode.parentNode.insertBefore(neoReplaceSection, downloadLinkNode);

    observeClick(replaceButton, async (node, event) => {
        event.preventDefault();
        let didReplace = false;
        await openReplaceDialog({ imgUrl: jsVar("neoReplacePostDetailImgUrl"), onUpdateCallback: () => { didReplace = true; } });
        if (didReplace) { reloadPage(); }
    });
});
