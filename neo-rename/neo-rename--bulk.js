import { observeClick, observeOnce } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";
import { openRenameDialog } from "./neo-rename--dialog.js";

const selectTop = await observeOnce("#bulk-action-selector-top"); const selectBottom = await observeOnce("#bulk-action-selector-bottom");
const optionValue = "neo-rename--bulk";
const optionTop    = document.createElement("option"); optionTop.value    = optionValue; optionTop.textContent    = neo__("neoRename", "neoRename"); selectTop.appendChild(optionTop);
const optionBottom = document.createElement("option"); optionBottom.value = optionValue; optionBottom.textContent = neo__("neoRename", "neoRename"); selectBottom.appendChild(optionBottom);

const buttonTop = await observeOnce("#doaction"); const buttonBottom = await observeOnce("#doaction2");
for (const [buttonNode, selectNode] of [[buttonTop, selectTop], [buttonBottom, selectBottom]]) {
    observeClick(buttonNode, async (node, event) => {
        const val = selectNode.value; if (val !== optionValue) { return; }
        event.preventDefault();

        const ids = []; document.querySelectorAll('#posts-filter table input[name="media[]"]:checked').forEach(n => { if (n.value) { ids.push(n.value); } });
        const urls = []; for (const id of ids) { const row = document.getElementById("post-" + id); if (row) { urls.push(row.querySelector(".neo-rename--media-library-list-inline-rename-button").getAttribute("data-neo-rename--img-url")); } }

        openRenameDialog({ filterInputText: "", inputMode: "find-replace", onlyIncludeImgUrls: urls.length > 0 ? urls : null });
    });
}
