import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { observeOnce } from "./_global--observer.js";

(async () => {
    observeOnce("#menu-tools .wp-menu-name", async (toolsMenuNameNode) => {
        const toolsLabelNode = document.createElement("span");
        toolsLabelNode.classList.add("neo-settings--tools-menu-label");
        toolsLabelNode.append(...toolsMenuNameNode.childNodes);
        toolsMenuNameNode.appendChild(toolsLabelNode);
        (await neoLoadInterfaceFunc("neo-rename", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-settings--tools-menu-label", "right");
        toolsMenuNameNode.closest("#menu-tools").addEventListener("pointerenter", () => toolsLabelNode.dispatchEvent(new Event("pointerenter")));
        if (toolsMenuNameNode.closest("#menu-tools").matches(":hover")) { toolsLabelNode.dispatchEvent(new Event("pointerenter")); }
    });
    observeOnce("#menu-tools a[href=\"tools.php?page=neowp\"]", async (neoWpMenuLinkNode) => {
        const neoWpLabelNode = document.createElement("span");
        neoWpLabelNode.classList.add("neo-settings--submenu-label");
        neoWpLabelNode.append(...neoWpMenuLinkNode.childNodes);
        neoWpMenuLinkNode.appendChild(neoWpLabelNode);
        (await neoLoadInterfaceFunc("neo-rename", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-settings--submenu-label", "right");
        if (neoWpLabelNode.getClientRects().length > 0) { document.querySelector(".neo-settings--tools-menu-label")?.dispatchEvent(new Event("pointerenter")); }
        neoWpMenuLinkNode.addEventListener("pointerenter", () => neoWpLabelNode.dispatchEvent(new Event("pointerenter")));
        if (neoWpMenuLinkNode.matches(":hover")) { neoWpLabelNode.dispatchEvent(new Event("pointerenter")); }
    });
})();
