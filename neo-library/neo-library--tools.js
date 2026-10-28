import { neoWarn, neoError } from "./_global--log.js";
import { setCookie } from "./_global--cookie.js";
import { addQueryParam } from "./_global--url-helper.js";
import { extractJson } from "./_global--extract-json.js";
import { neo__ } from "./_global--translation.js";
import { reloadPage } from "./_global-reload-page.js";
import { getLibraryTable, addRow, updateImageCount } from "./neo-library--globals.js";
import { updateRowAttribute } from "./neo-library--render.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { observeClick, observeOnce } from "./_global--observer.js";

import { onRenameCompleted } from "./neo-library--event-listeners.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { requireNeoPluginForLibraryAction } from "./neo-library--plugin-requirement.js";

import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { matchesSearchText } from "./_global-search.js";
import { isModuleAvailable } from "./_global--interface.js";

observeClick("#neo-library--create-row", async () => {
    if (!(await requireNeoPluginForLibraryAction("draw"))) { return; }
    let newImgUrl = "";
    new (await neoLoadInterfaceFunc("neo-library", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))()
        .on("save", ({ imgUrl }) => newImgUrl = imgUrl)
        .on("close", async () => {
            if (!newImgUrl) { return; }
            const row = await addRow();
            updateRowAttribute(row, "data-img-url", newImgUrl);
            updateRowAttribute(row, "data-message", `<span class="neo-library--check-animation"><img class="neo-library--lucide-icon" src="${pluginUrl()}/_global-lucide-icons-thirdparty/sparkles.svg" alt=""></span> ` + neo__("Created", "Erstellt"));
            updateRowAttribute(row, "data-upload-date",   new Date().toISOString().replace('T', ' ').substring(0, 19));
            updateRowAttribute(row, "data-modified-date", new Date().toISOString().replace('T', ' ').substring(0, 19));
            updateRowAttribute(row, "data-state", "download-pending");
            updateRowAttribute(row, "data-is-neodraw", "true");
        })
        .open();
});

if (isModuleAvailable("neo-draw")) {
    try {
        if ((await neoLoadInterfaceFunc("neo-library", "neo-draw--editor-dialog.js", "interfaceConsumeNeoDrawCreateQueryParamSuppressErrorPopup20260811"))()) { (await observeOnce("#neo-library--create-row")).click(); }
    } catch (error) {
        neoWarn(error);
    }
}

document.addEventListener("keydown", async (evt) => { if (evt.key === "Shift") { (await getLibraryTable()).classList.add("neo-library--show-shift-tooltips"); } });
document.addEventListener("keyup",   async (evt) => { if (evt.key === "Shift") { (await getLibraryTable()).classList.remove("neo-library--show-shift-tooltips"); } });

observeClick("#neo-library--mode-switch-list",        evt => { setCookie("neo-library--use-neo-library", "false"); location.href = addQueryParam(jsVar("neoLibraryMediaLibraryUrl"), "mode", "list"); });
observeClick("#neo-library--mode-switch-grid",        evt => { setCookie("neo-library--use-neo-library", "false"); location.href = addQueryParam(jsVar("neoLibraryMediaLibraryUrl"), "mode", "grid"); });
observeClick("#neo-library--mode-switch-neo-library", evt => { setCookie("neo-library--use-neo-library", "true");  location.reload(); });
(await neoLoadInterfaceFunc("neo-library", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))("#neo-library--mode-switch-neo-library");
if (location.hash === "#neo-library--open-tutorial") { window.history.replaceState(null, "", location.pathname + location.search); }

if (localStorage.getItem("neo-library--old-media-library-hint-confirmed") !== "true") {
    observeOnce("#neo-library--old-media-library-hint", node => node.style.cssText = "");
    observeClick("#neo-library--old-media-library-hint-button", () => {
        localStorage.setItem("neo-library--old-media-library-hint-confirmed", "true");
        observeOnce("#neo-library--old-media-library-hint", node => node.style.cssText = "display: none;");
    });
}

observeClick("#neo-library--bulk-rename-button", async (bulkRenameButton) => {
    if (!(await requireNeoPluginForLibraryAction("rename"))) { return; }
    await (await neoLoadInterfaceFunc("neo-library", "neo-rename--dialog.js", "interfaceOpenRenameDialog20250813"))({ filterInputText: "", inputMode: "find-replace", onUpdateCallback: onRenameCompleted });
});

let clearCacheAnimationTimer;
let clearCacheTooltipResetTimer;
observeClick("#neo-library--clear-cache-button", async (clearCacheButton) => {
    clearTimeout(clearCacheAnimationTimer); clearCacheButton.classList.remove("neo-library--clear-animation");
    await new Promise(requestAnimationFrame);
    clearCacheButton.classList.add("neo-library--clear-animation");
    clearCacheAnimationTimer = setTimeout(() => clearCacheButton.classList.remove("neo-library--clear-animation"), 800);

    clearTimeout(clearCacheTooltipResetTimer);
    clearCacheButton.classList.add("neo-library--force-show-tooltip");
    clearCacheButton.setAttribute("data-neo-library--tooltip", neo__("Clearing cache...", "Cache wird geleert..."));
    let resp;
    try {
        resp = await fetchEndpoint("/wp-json/neo/library-clear-cache", { method: "POST" });
    } catch (err) { neoError("Error", err); clearCacheButton.setAttribute("data-neo-library--tooltip", neo__("Error", "Fehler")); return; }
    try {
        await extractJson(resp);

        clearCacheButton.setAttribute("data-neo-library--tooltip", neo__("Cache cleared", "Cache geleert"));
        await new Promise(resolve => setTimeout(resolve, 2000));
        reloadPage();
    } catch (err) {
        neoError("Server error", err);
        clearCacheButton.setAttribute("data-neo-library--tooltip", err.message || neo__("Server Error", "Serverfehler"));
        throw err;
    }
});

let sortSelectButton;
let sortSelect;
export function getSortMode() { return sortSelect?.value ?? "upload-date"; }
export function isNewNeoDrawImageRow(row) { return /neodraw(-\d+)?\.svg$/.test(row.getAttribute("data-img-url")); }
let sortTableAnimationTimer;
async function sortTableRows(isFirstSort) {
    if (!isFirstSort) {
        clearTimeout(sortTableAnimationTimer); sortSelectButton.classList.remove("neo-library--sort-animation");
        await new Promise(requestAnimationFrame);
        sortSelectButton.classList.add("neo-library--sort-animation");
        sortTableAnimationTimer = setTimeout(() => { sortSelectButton.classList.remove("neo-library--sort-animation"); }, 400);
    }

    const libraryTableNode = await getLibraryTable();
    const allRows = [...libraryTableNode.querySelectorAll(".neo-library--row[data-img-url]")];
    allRows.sort((a, b) => {
        const compareDates = (a, b, attributeName) => {
            if (a.getAttribute(attributeName) === b.getAttribute(attributeName)) { return 0; }
            if (!a.getAttribute(attributeName)) { return -1; }
            if (!b.getAttribute(attributeName)) { return 1; }
            return - a.getAttribute(attributeName).localeCompare(b.getAttribute(attributeName));
        };
        if (getSortMode() === "upload-date")   { return compareDates(a, b, "data-upload-date"); }
        if (getSortMode() === "modified-date") { return compareDates(a, b, "data-modified-date"); }
        if (getSortMode() === "title" && isNewNeoDrawImageRow(a) !== isNewNeoDrawImageRow(b)) { return isNewNeoDrawImageRow(a) ? -1 : 1; }
        if (getSortMode() === "url"   && isNewNeoDrawImageRow(a) !== isNewNeoDrawImageRow(b)) { return isNewNeoDrawImageRow(a) ? -1 : 1; }
        if (getSortMode() === "title") { return (a.getAttribute("data-title") ?? a.getAttribute("data-img-url").split("/").pop()).localeCompare((b.getAttribute("data-title") ?? b.getAttribute("data-img-url").split("/").pop())); }
        if (getSortMode() === "url")   { return a.getAttribute("data-img-url").localeCompare(b.getAttribute("data-img-url")); }
        neoWarn("Unknown sort option: " + getSortMode());
    });
    for (const row of allRows) { libraryTableNode.appendChild(row); }
}
customElements.whenDefined("neo-select-neo-library").then(async () => {
    sortSelectButton = await observeOnce("#neo-library--sort-select-button");
    sortSelect = await observeOnce("#neo-library--sort-select");
    sortSelect.value = localStorage.getItem("neo-library--sort-select") || "upload-date";
    sortSelect.addEventListener("change", async () => { localStorage.setItem("neo-library--sort-select", getSortMode()); await sortTableRows(); });
    if (getSortMode() !== "upload-date") { await sortTableRows(true); }
});

const searchInput = await observeOnce("#neo-library--search-input");
searchInput.addEventListener("input", async () => {
    const search = document.getElementById("neo-library--search-input").value.toLowerCase();
    const libraryTableNode = await getLibraryTable();
    libraryTableNode.classList.toggle("neo-library--search-active", !!search);
    searchInput.setAttribute("data-neo-library--search-state", "pending");
    const rows = libraryTableNode.querySelectorAll(".neo-library--row[data-img-url]");
    let rowCounter = 0;
    let resultWasFound = false;
    for (const row of rows) {
        const imgUrl = row.getAttribute("data-img-url");
        const filename = imgUrl.split("/").pop();
        const title = row.getAttribute("data-title");
        const author = row.getAttribute("data-author");

        const searchMatches = matchesSearchText(search, [filename, title, author], [imgUrl]);

        if (searchMatches) {
            resultWasFound = true;
            row.classList.remove("neo-library--row-search-hidden");
        } else {
            row.classList.add("neo-library--row-search-hidden");
        }
        rowCounter ++;
        if (rowCounter % 10000 === 0) {
            await new Promise(requestAnimationFrame);

            if (document.getElementById("neo-library--search-input").value.toLowerCase() !== search) { searchInput.classList.remove("neo-library--search-pending"); return; }
        }
    }
    if (search === "") { searchInput.setAttribute("data-neo-library--search-state", "empty"); }
    else if (resultWasFound) { searchInput.setAttribute("data-neo-library--search-state", "success"); }
    else                     { searchInput.setAttribute("data-neo-library--search-state", "nothing"); }
    updateImageCount();
});
window.addEventListener("pageshow", () => setTimeout(() => searchInput.dispatchEvent(new Event("input"))));

const searchSuggestions = JSON.parse(localStorage.getItem("neo-library--search-suggestions") || "[]");
function neoUpdateSearchSuggestions() {
    const suggestionsDatalist = document.getElementById("neo-library--search-input-suggestions");
    if (!suggestionsDatalist) { return; }
    suggestionsDatalist.innerHTML = searchSuggestions.map(suggestion => `<option value="${suggestion}"></option>`).join("");
}
neoUpdateSearchSuggestions();
searchInput.addEventListener("blur", () => {
    const search = document.getElementById("neo-library--search-input").value.toLowerCase();
    if (!search) { return; }
    if (searchSuggestions.includes(search)) { return; }
    searchSuggestions.unshift(search);
    searchSuggestions.splice(5);
    localStorage.setItem("neo-library--search-suggestions", JSON.stringify(searchSuggestions));
    neoUpdateSearchSuggestions();
});
