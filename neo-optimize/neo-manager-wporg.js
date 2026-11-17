import { observeClick } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";

observeClick(".neo-manager-wporg--install-button", (buttonNode, event) => {
    event.preventDefault();
    globalThis.tb_show("", buttonNode.getAttribute("href"));
    const popupNode = document.querySelector("#TB_window");
    popupNode.setAttribute("role", "dialog"); popupNode.setAttribute("aria-label", neo__("Plugin details", "Plugin-Details")); popupNode.classList.add("plugin-details-modal");
    popupNode.querySelector("#TB_iframeContent")?.setAttribute("title", neo__("Plugin details", "Plugin-Details"));
});
