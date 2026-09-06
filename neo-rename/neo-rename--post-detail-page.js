import { observeOnce, observeClick } from "./_global--observer.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { neo__ } from "./_global--translation.js";
import { reloadPage } from "./_global-reload-page.js";
import { openRenameDialog } from "./neo-rename--dialog.js";

observeOnce(".misc-pub-section.misc-pub-download", (downloadLinkNode) => {
    if (window.self !== window.top) { return; }
    const neoRenameSection = document.createElement("div"); neoRenameSection.classList.add("misc-pub-section", "misc-pub-neorename");
    const renameButton = document.createElement("button"); renameButton.className = "button button-secondary";
    renameButton.innerHTML = `<img alt="" src="${pluginUrl()}/img/_global--mini-icon.svg" style="height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.4em;">${neo__("neoRename", "neoRename")}`;
    neoRenameSection.appendChild(renameButton);
    downloadLinkNode.parentNode.insertBefore(neoRenameSection, downloadLinkNode);
    observeClick(renameButton, async (node, event) => {
        event.preventDefault();
        let didRename = false;
        await openRenameDialog({ filterInputText: document.querySelector("#attachment_url").value, onUpdateCallback: () => { didRename = true; } });
        if (didRename) { reloadPage(); }
    });
});
