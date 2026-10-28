import { neo__ } from "./_global--translation.js";
import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { fillRowsObserver } from "./neo-library--render.js";
import { observeOnce } from "./_global--observer.js";

export async function getLibraryTable() { getLibraryTable.node ??= await observeOnce("#neo-library--table"); return getLibraryTable.node; }
export async function getRowNode(imgUrl) { return (await getLibraryTable()).querySelector(`.neo-library--row[data-img-url="${imgUrl}"]`); }
export async function addRow() {
    const rowTemplateNode = new DomNodeHelper('<div class="neo-library--row"></div>').getNode();
    requestAnimationFrame(() => fillRowsObserver.observe(rowTemplateNode));
    (await observeOnce("#neo-library--upload-row")).insertAdjacentElement("afterend", rowTemplateNode);
    updateImageCount();
    return rowTemplateNode;
}
export async function updateImageCount() {
    await observeOnce("#neo-library--footer");
    const imageCount = (await getLibraryTable()).querySelectorAll(".neo-library--row:not(#neo-library--create-row, #neo-library--upload-row):not(.neo-library--row-search-hidden):not(.neo-library--deleting-animation)").length;
    document.querySelector("#neo-library--footer>.neo-library--image-count").textContent = imageCount.toLocaleString() + " " + neo__("images", "Bilder");
}
updateImageCount();
