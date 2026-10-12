import { isSafari } from "./_global--detect-safari.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";
import { neoError } from "./_global--log.js";
import { observeClick, addEventListenerWithInitialCall, delay, addEventListenerWithInitialCallMultiple } from "./_global--observer.js";
import { infiniteVirtualScroll } from "./_global--scroll-infinite-virtual.js";
import { addCacheBust, fitProtocolToFetchImgUrl, stripProtocol } from "./_global--url-helper.js";
import { getFileType } from "./_global-media-file-type.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";

import { pricingUrl } from "./_global-pricing-url.js";
import Swal from "./_global-sweetalert2.js";
import { neo__ } from "./_global--translation.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { isModuleAvailable } from "./_global--interface.js";
import { isPagebuilderOpen } from "./_global--pagebuilder-warning-detect.js";

import { neoLoadInterfaceFunc } from "./_global--interface.js";

import { cleanTitle, cleanPathRel, initCleanFileNameEmojiMap } from "./_global-clean-file-name.js";
import { setAiGenerationState } from "./_global--ai-generation-state.js";

const diffMatchPatchJsLoadingPromise = new Promise((resolve, reject) => {
    const scriptNode = document.createElement("script");
    scriptNode.src = pluginUrl() + "/_global-diff-match-patch-thirdparty/diff_match_patch.js";
    scriptNode.onload = () => resolve(); scriptNode.onerror = () => reject();
    document.head.appendChild(scriptNode);
});

let cssLoadingPromise = Promise.resolve();
if (!document.querySelector('link[data-neo-rename--dialog-css]')) {
    const linkNode = document.createElement("link");
    linkNode.rel = "stylesheet";
    linkNode.setAttribute("data-neo-rename--dialog-css", "true");
    cssLoadingPromise = new Promise((resolve, reject) => { linkNode.onload = () => resolve(); linkNode.onerror = () => reject(); });
    linkNode.href = pluginUrl() + "/neo-rename--dialog.css";
    document.head.appendChild(linkNode);
}

import("./_global-web-component-neo-select.js");

const transparentBase64 = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
const html = `
<div id="neo-rename--dialog" class="neo-rename--dialog">
    <div class="neo-rename--dialog-backdrop"></div>
    <div class="neo-rename--dialog-box">
        <div class="neo-rename--dialog-preview-section">
            <button id="neo-rename--dialog-navigation-previous-button" class="neo-rename--dialog-navigation-button neo-rename--dialog-navigation-previous-button" type="button" title="${neo__("Previous image", "Vorheriges Bild")}" aria-label="${neo__("Previous image", "Vorheriges Bild")}"></button>
            <img id="neo-rename--dialog-image-preview-image" class="neo-rename--dialog-image-preview-image neo-rename--dialog-image-preview-image-loading" src="${transparentBase64}" alt="" fetchpriority="high">
            <button id="neo-rename--dialog-navigation-next-button" class="neo-rename--dialog-navigation-button neo-rename--dialog-navigation-next-button" type="button" title="${neo__("Next image", "Nächstes Bild")}" aria-label="${neo__("Next image", "Nächstes Bild")}"></button>
        </div>
        <div class="neo-rename--dialog-input-section">
            <div class="neo-rename--dialog-input-layout">
                <div class="neo-rename--dialog-alt-text-row">
                    <label class="neo-rename--dialog-alt-text-label" for="neo-rename--dialog-alt-text-input">${neo__("Alt text", "Alt-Text")}</label>
                    <div class="neo-rename--dialog-alt-text-input-wrapper">
                        <textarea class="neo-rename--dialog-alt-text-input" id="neo-rename--dialog-alt-text-input" rows="1" placeholder="${neo__("Alt text...", "Alt-Text...")}"></textarea>
                        <neo-info-tooltip-neo-rename class="neo-rename--dialog-alt-text-ai-button-tooltip neo-rename--dialog-input-icon-tooltip" no-click-open instant-hover><button slot="icon" class="neo-rename--dialog-alt-text-ai-button" id="neo-rename--dialog-alt-text-ai-button" aria-label="${neo__("Generate alt text with AI", "Alt-Text mit AI generieren")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/sparkles.svg" alt=""></button>${neo__("Generate alt text with AI", "Alt-Text mit AI generieren")}</neo-info-tooltip-neo-rename>
                        <neo-info-tooltip-neo-rename class="neo-rename--dialog-alt-text-undo-button-tooltip neo-rename--dialog-input-icon-tooltip" no-click-open instant-hover><button slot="icon" class="neo-rename--dialog-alt-text-undo-button" id="neo-rename--dialog-alt-text-undo-button" aria-label="${neo__("Undo AI alt text", "AI-Alt-Text rückgängig machen")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/undo-2.svg" alt=""></button>${neo__("Undo AI alt text", "AI-Alt-Text rückgängig machen")}</neo-info-tooltip-neo-rename>
                    </div>
                </div>
                <div class="neo-rename--dialog-input-main-row">
                    <div class="neo-rename--dialog-input-column">
                <div class="neo-rename--dialog-input-column-row" data-neo-rename--placeholder="${neo__("Search...", "Suchen...")}">
                    <div class="neo-rename--dialog-select-wrapper">
                        <neo-select-neo-rename class="neo-rename--dialog-select" id="neo-rename--dialog-select-filter" selected-display="first-token">
                            <option value="search" selected data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/search.svg">${neo__("Search", "Suchen")}</option>
                            <option value="regex" data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/wand-sparkles.svg" show-pro-crown-if-not-pro>${neo__("Regex", "Regex")}</option>
                        </neo-select-neo-rename>
                    </div>
                    <input type="search" class="neo-rename--dialog-input" id="neo-rename--dialog-filter-input">
                    <neo-info-tooltip-neo-rename class="neo-rename--dialog-filter-clear-button-tooltip neo-rename--dialog-input-icon-tooltip" no-click-open instant-hover><button slot="icon" class="neo-rename--dialog-clear-button" id="neo-rename--dialog-filter-clear-button" aria-label="${neo__("Clear search", "Suche leeren")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/x.svg" alt=""></button>${neo__("Clear search", "Suche leeren")}</neo-info-tooltip-neo-rename>
                    <neo-info-tooltip-neo-rename class="neo-rename--dialog-explain-mode-button neo-rename--dialog-explain-mode-button-disabled" id="neo-rename--dialog-explain-filter-button" default-icon-color="#606060"></neo-info-tooltip-neo-rename>
                </div>
                <div class="neo-rename--dialog-input-column-row neo-rename--dialog-input-column-row-replace" data-neo-rename--placeholder="${neo__("Enter image title...", "Bildtitel eingeben...")}">
                    <div class="neo-rename--dialog-select-wrapper">
                        <neo-select-neo-rename class="neo-rename--dialog-select neo-rename--dialog-select-tool" id="neo-rename--dialog-select-tool" value="single" selected-display="first-token">
                            <optgroup label="${neo__("Single Rename", "Einzelbild umbenennen")}">
                                <option value="single" data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/pencil.svg">${neo__("Rename", "Umbenennen")}</option>
                            </optgroup>
                            <optgroup label="${neo__("Bulk Rename", "Bulk Umbenennen")}">
                                <option value="find-replace"     data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/replace.svg">${neo__("Find & Replace", "Suchen & Ersetzen")}</option>
                                <option value="prepend"          data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/arrow-left-to-line.svg">${neo__("Prepend", "Vorne anfügen")}</option>
                                <option value="append"           data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/arrow-right-to-line.svg">${neo__("Append", "Hinten anfügen")}</option>
                                <option value="new-name"         data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/text-cursor-input.svg">${neo__("New name", "Neuer Name")}</option>
                                <option value="remove"           data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/x.svg">${neo__("Remove", "Entfernen")}</option>
                                <option value="clean"            data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/eraser.svg">${neo__("Clean", "Bereinigen")}</option>
                                <option value="remove-subfolder" data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/folder-minus.svg" show-pro-crown-if-not-pro>${neo__("Remove subfolder", "Unterordner entfernen")}</option>
                                <option value="derive-title"     data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/tag.svg" show-pro-crown-if-not-pro>${neo__("Title from filename", "Titel aus Dateiname")}</option>
                                <option value="derive-filename"  data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/save.svg" show-pro-crown-if-not-pro>${neo__("Filename from title", "Dateiname aus Titel")}</option>
                                <option value="undo"             data-icon-url="${pluginUrl()}/_global-lucide-icons-thirdparty/undo-2.svg" show-pro-crown-if-not-pro>${neo__("Undo last rename", "Letzte Umbenennung rückgängig machen")}</option>
                            </optgroup>
                        </neo-select-neo-rename>
                    </div>
                    <input type="search" class="neo-rename--dialog-input" id="neo-rename--dialog-title-input" value="" list="neo-rename--dialog-suggestion-list">
                    <neo-info-tooltip-neo-rename class="neo-rename--dialog-undo-clean-button-tooltip neo-rename--dialog-input-icon-tooltip" no-click-open instant-hover tooltip-offset-y="8"><button slot="icon" class="neo-rename--dialog-undo-clean-button" id="neo-rename--dialog-undo-clean-button" aria-label="${neo__("Undo cleaning", "Aufräumen rückgängig machen")}" disabled>↺</button>${neo__("Undo cleaning", "Aufräumen rückgängig machen")}</neo-info-tooltip-neo-rename>
                    <neo-info-tooltip-neo-rename class="neo-rename--dialog-title-ai-button-tooltip neo-rename--dialog-input-icon-tooltip" no-click-open instant-hover tooltip-offset-y="8"><button slot="icon" class="neo-rename--dialog-title-ai-button neo-rename--dialog-ai-icon-button" id="neo-rename--dialog-title-ai-button" aria-label="${neo__("Generate title with AI", "Titel mit AI generieren")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/sparkles.svg" alt=""></button>${neo__("Generate title with AI", "Titel mit AI generieren")}</neo-info-tooltip-neo-rename>
                    <neo-info-tooltip-neo-rename class="neo-rename--dialog-clear-button-tooltip neo-rename--dialog-input-icon-tooltip" no-click-open instant-hover><button slot="icon" class="neo-rename--dialog-clear-button" id="neo-rename--dialog-clear-button" aria-label="${neo__("Clear input", "Eingabe löschen")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/x.svg" alt=""></button>${neo__("Clear input", "Eingabe löschen")}</neo-info-tooltip-neo-rename>
                    <neo-info-tooltip-neo-rename class="neo-rename--dialog-explain-mode-button neo-rename--dialog-explain-mode-button-disabled" id="neo-rename--dialog-explain-tool-button" default-icon-color="#606060"></neo-info-tooltip-neo-rename>
                </div>
                    </div>
                    <div class="neo-rename--dialog-input-column neo-rename--dialog-button-column">
                        <neo-info-tooltip-neo-rename id="neo-rename--dialog-rename-button-disabled-tooltip" disabled>
                            <button slot="icon" id="neo-rename--dialog-rename-button" class="neo-rename--dialog-rename-button">
                                <span class="neo-rename--dialog-rename-button-text">${neo__("Rename", "Umbenennen")}</span>
                                <span class="neo-rename--spinner"></span>
                            </button>
                            ${neo__("There is nothing to rename.", "Es gibt nichts umzubenennen.")}
                        </neo-info-tooltip-neo-rename>
                    </div>
                </div>
            </div>
        </div>
        <div class="neo-rename--dialog-table-section">
            <div class="neo-rename--dialog-table">
                <div class="neo-rename--dialog-table-row neo-rename--dialog-table-row-header">
                    <div class="neo-rename--dialog-table-row-cell">
                        <input type="checkbox" checked class="neo-rename--dialog-table-checkbox" id="neo-rename--checkbox-title">
                        <label for="neo-rename--checkbox-title">${neo__("Title", "Titel")}</label>
                        <neo-info-tooltip-neo-rename>
                            <h3>${neo__("Title", "Titel")}</h3>
                            <ul>
                                <li>${neo__('Automatic capitalization with title capitalization<br><em>"edge-of-space.jpg" → Edge of Space</em>', 'Automatische <strong>Großschreibung</strong> mit Titel-Capitalization<br><em>"edge-of-space" → Edge of Space</em>')}</li>
                                <li>${neo__('For SEO, <strong>hyphens</strong> "-" are replaced with <strong>spaces</strong> " "<br><em>"Edge-of-Space" → Edge of Space</em>', 'Für SEO werden <strong>Bindestriche</strong> "-" durch <strong>Leerzeichen</strong> " " ersetzt<br><em>"Edge-of-Space" → Edge of Space</em>')}</li>
                            </ul>
                        </neo-info-tooltip-neo-rename>
                    </div>
                    <div class="neo-rename--dialog-table-row-cell">
                        <input type="checkbox" checked class="neo-rename--dialog-table-checkbox" id="neo-rename--checkbox-filename">
                        <label for="neo-rename--checkbox-filename">${neo__("Filename", "Dateiname")}</label>
                        <neo-info-tooltip-neo-rename>
                            <h3>${neo__("Filename and Slug", "Dateiname und Slug")}</h3>
                            <ul>
                                <li>${neo__('<strong>Emojis</strong> are converted to <strong>plain text</strong><br><em>"File Name 🎉" → file-name-party-popper.jpg</em>', '<strong>Emojis</strong> werden <strong>in Klartext</strong> umgewandelt<br><em>"File Name 🎉" → file-name-party-popper.jpg</em>')}</li>
                                <li>${neo__('<strong>Umlauts &amp; special characters are replaced or removed</strong><br><em>"Café in München" → cafe-in-muenchen.jpg</em>', '<strong>Umlaute &amp; Sonderzeichen</strong> werden <strong>ersetzt</strong> oder entfernt<br><em>"Café in München" → cafe-in-muenchen.jpg</em>')}</li>
                                <li>${neo__('Consistent spelling with <strong>hyphens &amp; extension</strong><br><em>"my image.jpeg" → my-image.jpg</em>', 'Einheitliche Schreibweise mit <strong>Bindestrichen &amp; Endung</strong><br><em>"my image.jp<strong>e</strong>g" → my<strong>-</strong>image.jpg</em>')}</li>
                            </ul>
                        </neo-info-tooltip-neo-rename>
                    </div>
                    <div class="neo-rename--dialog-table-row-cell">
                        <div class="neo-rename--dialog-checkbox-hide-unchanged-wrapper">
                            <input type="checkbox" class="neo-rename--dialog-table-checkbox" id="neo-rename--dialog-checkbox-hide-unchanged" disabled>
                            <label for="neo-rename--dialog-checkbox-hide-unchanged">${neo__("Hide unchanged", "Gleichbleibende ausblenden")}</label>
                        </div>
                    </div>
                </div>
                <div class="neo-rename--dialog-table-row-shadow-container"></div>
                <div class="neo-rename--dialog-table-row-scroll-wrapper" tabindex="0">
                    <div id="neo-rename--dialog-table-row-template" class="neo-rename--dialog-table-row">
                        <div class="neo-rename--dialog-table-row-cell">&nbsp;</div>
                        <div class="neo-rename--dialog-table-row-cell">&nbsp;</div>
                        <div class="neo-rename--dialog-table-row-cell">
                            <neo-info-tooltip-neo-rename class="neo-rename--dialog-action-button-tooltip" no-click-open instant-hover><button slot="icon" type="button" class="neo-rename--dialog-action-button neo-rename--dialog-action-button-preview" tabindex="-1" aria-label="${neo__("Open preview", "Vorschau öffnen")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/eye.svg" alt=""></button><span class="neo-rename--dialog-action-button-tooltip-label">${neo__("Open preview", "Vorschau öffnen")}</span></neo-info-tooltip-neo-rename>
                            <neo-info-tooltip-neo-rename class="neo-rename--dialog-action-button-tooltip" no-click-open instant-hover><button slot="icon" type="button" class="neo-rename--dialog-action-button neo-rename--dialog-action-button-exclude" tabindex="-1" aria-label="${neo__("Exclude from rename", "Vom Umbenennen ausschließen")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/list-x.svg" alt=""></button><span class="neo-rename--dialog-action-button-tooltip-label">${neo__("Exclude from rename", "Vom Umbenennen ausschließen")}</span></neo-info-tooltip-neo-rename>
                            <button class="neo-rename--dialog-rename-status-button" tabindex="-1"></button>
                        </div>
                    </div>
                </div>
                <div class="neo-rename--dialog-table-empty-state"></div>
                <div class="neo-rename--dialog-table-row neo-rename--dialog-table-row-footer">
                    <span id="neo-rename--dialog-table-footer-text">${neo__("Showing ... of ... results", "Zeige ... von ... Ergebnisse")}</span>
                    <div id="neo-rename--dialog-progress-bar" style="--progress: 0.0;"></div>
                </div>
            </div>
            <div class="neo-rename--dialog-table-pro-overlay neo-rename--dialog-table-pro-overlay-hidden">
                <neo-button-neo-rename href="${pricingUrl()}" target="_blank" class="neo-rename--dialog-table-pro-overlay-button" show-pro-crown>${neo__("Get neoPro", "neoPro holen")}</neo-button-neo-rename>
            </div>
        </div>
        ${isModuleAvailable("neo-feedback") ? `<neo-info-tooltip-neo-rename class="neo-rename--dialog-feedback-button" no-click-open instant-hover><button slot="icon" type="button" aria-label="${neo__("Give feedback", "Feedback geben")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/message-square.svg" alt=""></button>${neo__("Give feedback", "Feedback geben")}</neo-info-tooltip-neo-rename>` : ""}
        ${jsVar("neoRenameSettingsSectionUrl") ? `<neo-info-tooltip-neo-rename class="neo-rename--dialog-settings-button" no-click-open instant-hover><a slot="icon" href="${jsVar("neoRenameSettingsSectionUrl")}" target="_blank" rel="noopener noreferrer" aria-label="${neo__("Open settings", "Einstellungen öffnen")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/settings.svg" alt=""></a>${neo__("Open settings", "Einstellungen öffnen")}</neo-info-tooltip-neo-rename>` : ""}
        <button id="neo-rename--dialog-close-button" class="neo-rename--dialog-close-button" type="button" title="${neo__("Close", "Schließen")}" aria-label="${neo__("Close", "Schließen")}"><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/x.svg" alt=""></button>
    </div>
    <datalist id="neo-rename--dialog-suggestion-list"></datalist>
    <neo-media-preview-popup-neo-rename></neo-media-preview-popup-neo-rename>
</div>
`;

const fileExtensionRegex = /\.[a-zA-Z0-9]+$/;

function escapeRegExp(string) { return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"); }

export async function openRenameDialog({ filterInputText = "", inputMode = "single", onUpdateCallback = () => {}, onlyIncludeImgUrls = null, navigationImgUrls = null, initialTextValuesByImgUrl = {} }) {
    await cssLoadingPromise;

    if (document.querySelector("#neo-rename--dialog")) { return; }

    let dialog = document.createElement("div");
    document.body.appendChild(dialog);
    dialog.outerHTML = html;
    dialog = document.querySelector("#neo-rename--dialog");
    await customElements.whenDefined("neo-select-neo-rename");

    dialog.querySelector(".neo-rename--dialog-feedback-button")?.addEventListener("click", async () => { await (await neoLoadInterfaceFunc("neo-rename", "neo-feedback.js", "interfaceOpenFeedbackDialog20260802"))({ swalContainerClass: "neo-rename--dialog-swal" }); });

    const imagePreview             = dialog.querySelector("#neo-rename--dialog-image-preview-image");
    const selectFilter             = dialog.querySelector("#neo-rename--dialog-select-filter");
    const selectTool               = dialog.querySelector("#neo-rename--dialog-select-tool");
    const filterInput              = dialog.querySelector("#neo-rename--dialog-filter-input");
    const titleInput               = dialog.querySelector("#neo-rename--dialog-title-input");
    const altTextInput             = dialog.querySelector("#neo-rename--dialog-alt-text-input");
    const altTextInputRow          = dialog.querySelector(".neo-rename--dialog-alt-text-row");
    const altTextInputWrapper      = dialog.querySelector(".neo-rename--dialog-alt-text-input-wrapper");
    const buttonAltTextAi          = dialog.querySelector("#neo-rename--dialog-alt-text-ai-button");
    const buttonAltTextUndo        = dialog.querySelector("#neo-rename--dialog-alt-text-undo-button");
    const buttonNavigationPrevious = dialog.querySelector("#neo-rename--dialog-navigation-previous-button");
    const buttonNavigationNext     = dialog.querySelector("#neo-rename--dialog-navigation-next-button");
    const filterInputRow           = filterInput.closest(".neo-rename--dialog-input-column-row");
    const titleInputRow            = titleInput.closest(".neo-rename--dialog-input-column-row");
    const inputColumn              = dialog.querySelector(".neo-rename--dialog-input-column");
    const buttonRename             = dialog.querySelector("#neo-rename--dialog-rename-button");
    const buttonClose              = dialog.querySelector("#neo-rename--dialog-close-button");
    const buttonFilterClear        = dialog.querySelector("#neo-rename--dialog-filter-clear-button");
    const buttonExplainFilter      = dialog.querySelector("#neo-rename--dialog-explain-filter-button");
    const buttonClear              = dialog.querySelector("#neo-rename--dialog-clear-button");
    const buttonTitleAi            = dialog.querySelector("#neo-rename--dialog-title-ai-button");
    const buttonUndoClean          = dialog.querySelector("#neo-rename--dialog-undo-clean-button");
    const buttonExplainTool        = dialog.querySelector("#neo-rename--dialog-explain-tool-button");
    const checkboxTitle            = dialog.querySelector("#neo-rename--checkbox-title");
    const checkboxFilename         = dialog.querySelector("#neo-rename--checkbox-filename");
    const checkboxHideUnchanged    = dialog.querySelector("#neo-rename--dialog-checkbox-hide-unchanged");
    const labelTitle               = dialog.querySelector('label[for="neo-rename--checkbox-title"]');
    const labelFilename            = dialog.querySelector('label[for="neo-rename--checkbox-filename"]');
    const progressBar              = dialog.querySelector("#neo-rename--dialog-progress-bar");
    const sectionPreview           = dialog.querySelector(".neo-rename--dialog-preview-section");
    const sectionTable             = dialog.querySelector(".neo-rename--dialog-table-section");
    const sectionTableProOverlay   = dialog.querySelector(".neo-rename--dialog-table-pro-overlay");
    const tableScrollWrapper       = dialog.querySelector(".neo-rename--dialog-table-row-scroll-wrapper");
    const suggestionList           = dialog.querySelector("#neo-rename--dialog-suggestion-list");
    const tableEmptyState          = dialog.querySelector(".neo-rename--dialog-table-empty-state");
    const mediaPreviewPopup        = dialog.querySelector("neo-media-preview-popup-neo-rename");
    const altTextMeasureNode       = document.createElement("span");
    altTextMeasureNode.className = "neo-rename--dialog-alt-text-measure"; altTextInputWrapper.appendChild(altTextMeasureNode);
    if (inputMode === "single") {
        (await neoLoadInterfaceFunc("neo-rename", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-rename--dialog-select-tool", "top", "click");
    }

    addEventListenerWithInitialCallMultiple([[filterInput, "input"], [titleInput, "input"], [altTextInput, "input"]], () => { filterInputRow?.toggleAttribute("data-neo-rename--input-has-value", filterInput.value !== ""); titleInputRow?.toggleAttribute("data-neo-rename--input-has-value", titleInput.value !== ""); altTextInputRow?.toggleAttribute("data-neo-rename--input-has-value", altTextInput.value !== ""); });

    const updateAltTextInputLayout = () => { altTextMeasureNode.textContent = altTextInput.value || altTextInput.placeholder; altTextInputWrapper.style.width = Math.max(140, Math.ceil(altTextMeasureNode.scrollWidth) + 16) + "px"; altTextInput.style.height = "0px"; const altTextInputHeight = Math.min(altTextInput.scrollHeight, parseFloat(getComputedStyle(altTextInput).maxHeight)); altTextInput.style.height = altTextInputHeight + "px"; altTextInputWrapper.style.height = altTextInputHeight + "px"; };
    addEventListenerWithInitialCall(altTextInput, "input", updateAltTextInputLayout);

    let disableNameInputBecauseOfTool = false, disableCheckboxesTitleAndFilenameBecauseOfTool = false, disableInputsBecauseOfProOverlay = false;
    let rows = [];
    let viewableRowsInTable = [];
    let singleNavigationImgUrls = []; let currentNavigationImgUrl = null; let singleNavigationInputBaseline = null;
    const getCurrentSingleRow = () => selectTool.value === "single" && viewableRowsInTable.length === 1 ? viewableRowsInTable[0] : null;
    const rowHasNoChanges = (row) => row.newTitle === row.title && row.newPathRel === row.pathRel && row.newAltText === row.altText;
    const getSingleActionChangeState = () => { const row = getCurrentSingleRow(); return { renameChanged: !!row && (row.newTitle !== row.title || row.newPathRel !== row.pathRel || row.newSlug !== row.slug), altTextChanged: !!row && row.newAltText !== row.altText }; };
    const setSingleNavigationInputBaseline = () => { singleNavigationInputBaseline = { filter: filterInput.value, title: titleInput.value, altText: altTextInput.value.trim() }; };
    const singleNavigationInputMatchesBaseline = () => singleNavigationInputBaseline && filterInput.value === singleNavigationInputBaseline.filter && titleInput.value === singleNavigationInputBaseline.title && altTextInput.value.trim() === singleNavigationInputBaseline.altText;
    const getSingleNavigationIndex = () => singleNavigationImgUrls.findIndex(imgUrl => stripProtocol(imgUrl) === stripProtocol(currentNavigationImgUrl ?? viewableRowsInTable[0]?.imgUrl ?? ""));
    function updateViewableRowsInTable() {
        viewableRowsInTable = rows.filter(row => {
            if (!row.isFilterMatching) { return false; }
            if (checkboxHideUnchanged.checked && (row.excluded || rowHasNoChanges(row))) { return false; }
            return true;
        });
        dialog.classList.toggle("neo-rename--dialog-show-alt-text", getFileType(getCurrentSingleRow()?.imgUrl ?? "") === "image");
    }
    function updateInputDisabledStates() {
        const hasRowsWithNoChanges = rows.some(row => row.isFilterMatching &&  (row.excluded ||  rowHasNoChanges(row)));
        const hasRowsWithChanges   = rows.some(row => row.isFilterMatching && !(row.excluded ||  rowHasNoChanges(row)));

        selectFilter.disabled          =  (getState() !== "edit");
        selectTool.disabled            =  (getState() !== "edit");
        checkboxHideUnchanged.disabled =  (getState() !== "edit") || !hasRowsWithNoChanges;
        filterInput.disabled           =  (getState() !== "edit") || disableInputsBecauseOfProOverlay;
        titleInput.disabled            =  (getState() !== "edit") || disableInputsBecauseOfProOverlay || disableNameInputBecauseOfTool;
        altTextInput.disabled          =  (getState() !== "edit") || disableInputsBecauseOfProOverlay || selectTool.value !== "single" || viewableRowsInTable.length !== 1;
        buttonAltTextAi.disabled       =  altTextInput.disabled || buttonAltTextAi.hasAttribute("data-neo-global--ai-generating");
        buttonAltTextUndo.disabled     =  altTextInput.disabled || buttonAltTextUndo.classList.contains("neo-rename--dialog-alt-text-button-hidden");
        buttonNavigationPrevious.disabled = (getState() !== "edit") || selectTool.value !== "single" || getSingleNavigationIndex() <= 0;
        buttonNavigationNext.disabled     = (getState() !== "edit") || selectTool.value !== "single" || getSingleNavigationIndex() < 0 || getSingleNavigationIndex() >= singleNavigationImgUrls.length - 1;
        checkboxTitle.disabled         =  (getState() !== "edit") || disableCheckboxesTitleAndFilenameBecauseOfTool;
        checkboxFilename.disabled      =  (getState() !== "edit") || disableCheckboxesTitleAndFilenameBecauseOfTool;
        buttonRename.disabled          = ((getState() !== "edit") || disableInputsBecauseOfProOverlay || !hasRowsWithChanges) && !["error", "done"].includes(getState());
        buttonFilterClear.disabled     =  (getState() !== "edit") || filterInput.value === "";
        buttonUndoClean.disabled       =  (getState() !== "edit") || selectTool.value !== "single" || rows.filter(r => r.isFilterMatching)[0]?.title === titleInput.value;
        buttonClear.disabled           =  (getState() !== "edit") || titleInput.value === "" || !buttonUndoClean.disabled;
        buttonTitleAi.disabled         =  (getState() !== "edit") || selectTool.value !== "single" || viewableRowsInTable.length !== 1 || buttonTitleAi.hasAttribute("data-neo-global--ai-generating");
        buttonExplainFilter.classList.toggle("neo-rename--dialog-explain-mode-button-disabled", filterInput.value !== "");
        buttonExplainTool.classList.toggle("neo-rename--dialog-explain-mode-button-disabled",   selectTool.value === "single" || !buttonUndoClean.disabled || !buttonClear.disabled || titleInput.value !== "");
        dialog.querySelector("#neo-rename--dialog-rename-button-disabled-tooltip").toggleAttribute("disabled", hasRowsWithChanges);
        checkboxHideUnchanged.closest(".neo-rename--dialog-checkbox-hide-unchanged-wrapper").classList.toggle("neo-rename--dialog-checkbox-hide-unchanged-hidden", checkboxHideUnchanged.disabled);
    }

    function updateRenameButtonState() {
        const buttonRenameTextNode = buttonRename.querySelector(".neo-rename--dialog-rename-button-text");
        const { renameChanged, altTextChanged } = getSingleActionChangeState();
        const singleActionButtonText = renameChanged && altTextChanged ? neo__("Rename & Save", "Umbenennen & Speichern") : (altTextChanged ? neo__("Save", "Speichern") : neo__("Rename", "Umbenennen"));
        switch (getState()) {
            case "init":  case "progress": buttonRenameTextNode.innerText = "";                                                                                                                     buttonRename.classList.toggle("neo-rename--loading", true);  break;
            case "edit":                   buttonRenameTextNode.innerText = inputMode === "remove-subfolder" ? neo__("Remove subfolders", "Unterordner entfernen") : singleActionButtonText; buttonRename.classList.toggle("neo-rename--loading", false); break;
            case "error": case "done":     buttonRenameTextNode.innerText = neo__("Close", "Schließen");                                                                                            buttonRename.classList.toggle("neo-rename--loading", false); break;
        }
    }

    function getState() { return dialog.getAttribute("data-neo-rename--state"); }
    function setState(state) {
        dialog.setAttribute("data-neo-rename--state", state);

        updateInputDisabledStates();

        updateRenameButtonState();
    }
    setState("init");

    document.activeElement.blur();
    filterInput.value = filterInputText; filterInput.dispatchEvent(new Event("input"));
    selectTool.value = inputMode;        selectTool.dispatchEvent(new Event("change"));
    addEventListenerWithInitialCall(selectTool, "change", () => { dialog.classList.toggle("neo-rename--dialog-show-bulk-options", selectTool.value !== "single"); dialog.classList.toggle("neo-rename--dialog-show-alt-text", getFileType(getCurrentSingleRow()?.imgUrl ?? "") === "image"); });

    if (inputMode === "remove-subfolder") { inputColumn.style.display = "none"; }

    let renameResolve, renameReject; const renamedPromise = new Promise((resolve, reject) => { renameResolve = resolve; renameReject = reject; });
    let inputChanged;
    let suppressInputChanged = false; let singleNavigationRenameFlow = { keepDialogOpen: false, targetImgUrl: null };

    let mediaItems = [];
    try {
        mediaItems = await fetchEndpoint("/wp-json/neo/rename-dialog-media-list").then(extractJson);
        mediaItems = [...(window.neoRenameMockedMediaList ?? []), ...mediaItems];
    } catch (error) {
        neoError(error);
        Swal.fire({
            customClass: { container: "neo-rename--dialog-swal" },
            icon: "error", title: neo__("Error", "Fehler"),
            text: neo__("Could not load image info!", "Konnte Bildinformationen nicht laden!") + " " + error.message,
            confirmButtonText: neo__("OK", "OK"),
        });
        closeDialog();
        return;
    }

    const getSingleInputTextValues = function (mediaItem) {
        const initialTextValues = Object.entries(initialTextValuesByImgUrl).find(([imgUrl]) => stripProtocol(imgUrl) === stripProtocol(mediaItem.imgUrl))?.[1] ?? {};
        const hasInitialTitle = Object.prototype.hasOwnProperty.call(initialTextValues, "title"); const hasInitialAltText = Object.prototype.hasOwnProperty.call(initialTextValues, "altText");
        let title = hasInitialTitle ? initialTextValues.title : mediaItem.title; if (!hasInitialTitle && title === mediaItem.pathRel.split("/").pop()) { title = cleanTitle(title); }
        return { title: hasInitialTitle ? title : cleanTitle(title), altText: hasInitialAltText ? initialTextValues.altText : (mediaItem.altText ?? "") };
    };

    let lastPreviewImageUrl = null;
    function showPreviewImageLoadingPlaceholder() {
        const imagePreviewRect = imagePreview.getBoundingClientRect(); if (imagePreviewRect.width && imagePreviewRect.height) { imagePreview.style.width = imagePreviewRect.width + "px"; imagePreview.style.height = imagePreviewRect.height + "px"; }
        imagePreview.classList.remove("neo-rename--dialog-file-type-icon"); imagePreview.removeAttribute("data-neo-rename--file-type"); sectionPreview.classList.remove("neo-rename--dialog-image-preview-image-hidden");
        imagePreview.classList.add("neo-rename--dialog-image-preview-image-loading"); imagePreview.src = transparentBase64; lastPreviewImageUrl = null;
    }
    async function showPreviewImage(imgUrl) {
        function hidePreviewImageLoadingSpinner() { imagePreview.classList.remove("neo-rename--dialog-image-preview-image-loading"); imagePreview.style.width = ""; imagePreview.style.height = ""; }
        if (imgUrl === null) { hidePreviewImageLoadingSpinner(); }
        if (imgUrl === lastPreviewImageUrl && lastPreviewImageUrl !== null) { return; } lastPreviewImageUrl = imgUrl;

        imagePreview.removeEventListener("load",  hidePreviewImageLoadingSpinner);
        imagePreview.removeEventListener("error", hidePreviewImageLoadingSpinner);

        imagePreview.classList.remove("neo-rename--dialog-file-type-icon");
        sectionPreview.classList.remove("neo-rename--dialog-image-preview-image-hidden");
        if (imgUrl) {
            const fileType = imgUrl === "done" ? "done" : getFileType(imgUrl);
            imagePreview.setAttribute("data-neo-rename--file-type", fileType);
            if (fileType === "image") {
                showPreviewImage.previewImageCachebustDate ??= Date.now();
                const imgUrlCacheBusted = fitProtocolToFetchImgUrl(addCacheBust(imgUrl, showPreviewImage.previewImageCachebustDate));
                const imagePreviewRect = imagePreview.getBoundingClientRect(); if (imagePreviewRect.width && imagePreviewRect.height) { imagePreview.style.width = imagePreviewRect.width + "px"; imagePreview.style.height = imagePreviewRect.height + "px"; }
                imagePreview.classList.add("neo-rename--dialog-image-preview-image-loading"); imagePreview.src = transparentBase64;
                imagePreview.addEventListener("load",  hidePreviewImageLoadingSpinner, { once: true });
                imagePreview.addEventListener("error", hidePreviewImageLoadingSpinner, { once: true });
                imagePreview.src = imgUrlCacheBusted;
            } else {
                imagePreview.src = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
                imagePreview.classList.add("neo-rename--dialog-file-type-icon");
                hidePreviewImageLoadingSpinner();
            }
        } else {
            imagePreview.removeAttribute("data-neo-rename--file-type");
            imagePreview.src = transparentBase64;
            if (inputMode === "remove-subfolder" && viewableRowsInTable.length === 0) { sectionPreview.classList.add("neo-rename--dialog-image-preview-image-hidden"); }
        }
    }

    showPreviewImage(mediaItems[0]?.imgUrl ?? null);

    observeClick(imagePreview, () => { if (!lastPreviewImageUrl) { return; } mediaPreviewPopup.open(lastPreviewImageUrl); });

    addEventListenerWithInitialCallMultiple([[selectFilter, "change"], [selectTool, "change"]], () => {
        if (selectTool.value === "single") {
            selectFilter.value = "search"; 
        }
        let showOverlay = false;
        if (selectFilter.value === "regex" || ["remove-subfolder", "derive-title", "derive-filename", "undo"].includes(selectTool.value)) {
            showOverlay = true; 
        }
        sectionTableProOverlay.classList.toggle("neo-rename--dialog-table-pro-overlay-hidden", !showOverlay);
        disableInputsBecauseOfProOverlay = showOverlay;
        setState(getState());
    });

    try { await initCleanFileNameEmojiMap(); } catch (error) { neoError(error); Swal.fire({ customClass: { container: "neo-rename--dialog-swal" }, icon: "error", title: neo__("Error", "Fehler"), text: neo__("Could not load emoji list!", "Konnte Emoji-Liste nicht laden!") + " " + error.message, confirmButtonText: neo__("OK", "OK") }); closeDialog(); return; }

    if (selectTool.value === "single") {
        if (filterInputText.startsWith("https://") || filterInputText.startsWith("http://")) {
            filterInputText = mediaItems.find(item => stripProtocol(item.imgUrl) === stripProtocol(filterInputText))?.pathRel ?? filterInputText;
            filterInput.value = filterInputText; filterInput.dispatchEvent(new Event("input"));
        }
        const foundMediaItem = mediaItems.find(item => item.pathRel === filterInputText);
        if (foundMediaItem) {
            const { title, altText } = getSingleInputTextValues(foundMediaItem);
            titleInput.value = title; titleInput.dispatchEvent(new Event("input")); altTextInput.value = altText; altTextInput.dispatchEvent(new Event("input")); setSingleNavigationInputBaseline(); updateRenameButtonState();
        }
    }

    rows = mediaItems.map((item, i) => ({
        ...item,
        isFilterMatching: false,
        excluded: false,
        newTitle: item.title, newPathRel: item.pathRel, newSlug: item.pathRel.split("/").pop().replace(fileExtensionRegex, ""), newAltText: item.altText ?? "",
        renameStatus: null,
    }));

    if (onlyIncludeImgUrls) { rows = rows.filter(item => onlyIncludeImgUrls.map(stripProtocol).includes(stripProtocol(item.imgUrl))); }

    const mediaItemsByUploadDateDesc = [...mediaItems].sort((a, b) => (b.uploadDate ?? "").localeCompare(a.uploadDate ?? ""));
    singleNavigationImgUrls = (navigationImgUrls ?? mediaItemsByUploadDateDesc.map(item => item.imgUrl)).filter(imgUrl => rows.some(row => stripProtocol(row.imgUrl) === stripProtocol(imgUrl)));
    currentNavigationImgUrl = rows.find(row => stripProtocol(row.imgUrl) === stripProtocol(filterInputText) || row.pathRel === filterInputText)?.imgUrl ?? singleNavigationImgUrls.find(imgUrl => stripProtocol(imgUrl) === stripProtocol(filterInputText)) ?? null;

    addEventListenerWithInitialCallMultiple([[filterInput, "input"], [titleInput, "input"], [altTextInput, "input"], [selectFilter, "change"], [selectTool, "change"]], () => {
        for (const row of rows) { row.excluded = false; }
    });

    function createRegexFromSearchOrRegexString(string, searchOrRegex = "search") {
        if (searchOrRegex === "search") {
            const searchRegexString = escapeRegExp(string).replace(/[- ]/g, "[- ]").replace(/\\\*/g, ".*");
            return new RegExp(searchRegexString, "ig");
        }
        if (searchOrRegex === "regex") {
            return createRegexFromSearchOrRegexString(string, "search"); 
        }
        throw new Error("Invalid searchOrRegex parameter: " + searchOrRegex);
    }
    function replaceWithoutEmptyMatch(subject, regex, replacement) {
        return subject.replace(regex, (...replaceArgs) => {
            const match = replaceArgs[0];
            const hasNamedGroups = typeof replaceArgs[replaceArgs.length - 1] === "object" && replaceArgs[replaceArgs.length - 1] !== null;
            const offset = hasNamedGroups ? replaceArgs[replaceArgs.length - 3] : replaceArgs[replaceArgs.length - 2];
            const captures = replaceArgs.slice(1, hasNamedGroups ? replaceArgs.length - 3 : replaceArgs.length - 2);
            const groups = hasNamedGroups ? replaceArgs[replaceArgs.length - 1] : undefined;
            if (match === "" && offset === subject.length) { return ""; }
            return String(replacement).replace(/\$(\$|&|0|[1-9][0-9]?|<[^>]*>)/g, (token, tokenValue) => {
                if (tokenValue === "$") { return "$"; }
                if (tokenValue === "0") { return match; }
                if (tokenValue === "&") { return match; }
                if (tokenValue.startsWith("<") && tokenValue.endsWith(">")) {
                    if (!groups) { return token; }
                    return groups[tokenValue.slice(1, -1)] ?? "";
                }
                const captureNumber = Number(tokenValue);
                if (captureNumber >= 1 && captureNumber <= captures.length) { return captures[captureNumber - 1] ?? ""; }
                if (tokenValue.length === 2) {
                    const firstDigitCaptureNumber = Number(tokenValue[0]);
                    if (firstDigitCaptureNumber >= 1 && firstDigitCaptureNumber <= captures.length) { return (captures[firstDigitCaptureNumber - 1] ?? "") + tokenValue[1]; }
                }
                return token;
            });
        });
    }

    await diffMatchPatchJsLoadingPromise;

    const templateRowNode = tableScrollWrapper.querySelector("#neo-rename--dialog-table-row-template");
    templateRowNode.removeAttribute("id");
    const rowHeight = templateRowNode.querySelector(".neo-rename--dialog-table-row-cell").offsetHeight;
    if (!rowHeight) { throw new Error("Row height could not be determined."); }
    tableScrollWrapper.innerHTML = "";
    const { updateRowData, rerenderList, getFirstFullyVisibleRowIndex, scrollToRowIndex } = infiniteVirtualScroll(tableScrollWrapper, (index) => (index < viewableRowsInTable.length ? rowHeight : undefined), (index) => {
        const row = viewableRowsInTable[index];

        let oldRowTitleForDiff = row.title, oldRowPathRelForDiff = row.pathRel, newRowTitleForDiff = row.newTitle, newRowPathRelForDiff = row.newPathRel;
        let oldRowTitleReplacedParts, oldRowPathRelReplacedParts, newRowTitleReplacedParts, newRowPathRelReplacedParts;
        let titleDiffWordByToken, pathRelDiffWordByToken;
        if (selectTool.value === "find-replace") {
            if (selectFilter.value === "search") {
                const filterRegex = createRegexFromSearchOrRegexString(filterInput.value, "search");
                const replaceWordsWithDiffTokens = function (oldText, newText, matchedTexts, replacementText) {
                    const words = [...new Set([...matchedTexts, replacementText].flatMap(text => text.split(/[- ]+/)).filter(Boolean))].sort((a, b) => b.length - a.length);
                    if (!words.length) { return [[oldText, newText], new Map()]; }
                    const tokenByWord = new Map(words.map((word, index) => [word, String.fromCharCode(0xE000 + index)]));
                    const wordRegex = new RegExp(words.map(escapeRegExp).join("|"), "g");
                    const replaceWords = text => text.replace(wordRegex, word => tokenByWord.get(word));
                    return [[replaceWords(oldText), replaceWords(newText)], new Map([...tokenByWord].map(([word, token]) => [token, word]))];
                };
                [[oldRowTitleForDiff,   newRowTitleForDiff],   titleDiffWordByToken]   = replaceWordsWithDiffTokens(row.title, row.newTitle, row.title.match(filterRegex) ?? [], titleInput.value);
                [[oldRowPathRelForDiff, newRowPathRelForDiff], pathRelDiffWordByToken] = replaceWordsWithDiffTokens(row.pathRel, row.newPathRel, row.pathRel.match(filterRegex) ?? [], cleanPathRel(titleInput.value));
            } else {
                row.title = row.title.replace("¶", ""); row.pathRel = row.pathRel.replace("¶", ""); row.newTitle = row.newTitle.replace("¤", ""); row.newPathRel = row.newPathRel.replace("¤", "");

                const filterRegex = createRegexFromSearchOrRegexString(filterInput.value, selectFilter.value);
                oldRowTitleReplacedParts   = []; oldRowTitleForDiff   = row.title.replace(filterRegex,      (m) => { if (m === row.title)      { return m; } oldRowTitleReplacedParts.push(m);   return "¶"; });
                oldRowPathRelReplacedParts = []; oldRowPathRelForDiff = row.pathRel.replace(filterRegex,    (m) => { if (m === row.pathRel)    { return m; } oldRowPathRelReplacedParts.push(m); return "¶"; });
                newRowTitleReplacedParts   = []; newRowTitleForDiff   = row.newTitle.replace(filterRegex,   (m) => { if (m === row.newTitle)   { return m; } newRowTitleReplacedParts.push(m);   return "¤"; });
                newRowPathRelReplacedParts = []; newRowPathRelForDiff = row.newPathRel.replace(filterRegex, (m) => { if (m === row.newPathRel) { return m; } newRowPathRelReplacedParts.push(m); return "¤"; });
            }
        }

        const diffObject = new diff_match_patch();
        let [diffTitle, diffPathRel] = [diffObject.diff_main(oldRowTitleForDiff, newRowTitleForDiff), diffObject.diff_main(oldRowPathRelForDiff, newRowPathRelForDiff)];
        diffObject.diff_cleanupSemantic(diffTitle); diffObject.diff_cleanupSemantic(diffPathRel);

        if (selectTool.value === "find-replace" && selectFilter.value === "search") {
            diffTitle   = diffTitle.map(part   => [part[0], part[1].replace(/[\uE000-\uF8FF]/g, token => titleDiffWordByToken.get(token)   ?? token)]);
            diffPathRel = diffPathRel.map(part => [part[0], part[1].replace(/[\uE000-\uF8FF]/g, token => pathRelDiffWordByToken.get(token) ?? token)]);
        } else if (selectTool.value === "find-replace") {
            let oldRowTitlePlaceholderIndex = 0, newRowTitlePlaceholderIndex = 0, oldRowPathRelPlaceholderIndex = 0, newRowPathRelPlaceholderIndex = 0;
            diffTitle   = diffTitle.map(part   => [part[0], part[1].replace(/¶/g, () => { return oldRowTitleReplacedParts[oldRowTitlePlaceholderIndex++]; })    .replace(/¤/g, () => { return newRowTitleReplacedParts[newRowTitlePlaceholderIndex++]; })]);
            diffPathRel = diffPathRel.map(part => [part[0], part[1].replace(/¶/g, () => { return oldRowPathRelReplacedParts[oldRowPathRelPlaceholderIndex++]; }).replace(/¤/g, () => { return newRowPathRelReplacedParts[newRowPathRelPlaceholderIndex++]; })]);
        }

        const cleanedDiffTitle   = []; for (let i = 0; i < diffTitle.length;   i++) { if (diffTitle[i][0]   === -1 && diffTitle[i + 1]   && diffTitle[i + 1][0]   === 1 && diffTitle[i][1]   === diffTitle[i + 1][1])   { cleanedDiffTitle.push([0, diffTitle[i][1]]);     i++; continue; } cleanedDiffTitle.push(diffTitle[i]); }     diffTitle = cleanedDiffTitle;
        const cleanedDiffPathRel = []; for (let i = 0; i < diffPathRel.length; i++) { if (diffPathRel[i][0] === -1 && diffPathRel[i + 1] && diffPathRel[i + 1][0] === 1 && diffPathRel[i][1] === diffPathRel[i + 1][1]) { cleanedDiffPathRel.push([0, diffPathRel[i][1]]); i++; continue; } cleanedDiffPathRel.push(diffPathRel[i]); } diffPathRel = cleanedDiffPathRel;

        let [diffTitleHtml, diffPathRelHtml] = [diffObject.diff_prettyHtml(diffTitle), diffObject.diff_prettyHtml(diffPathRel)];

        const newRowNode = templateRowNode.cloneNode(true);

        newRowNode.children[0].innerHTML = `<div class="neo-rename--dialog-diff-ellipsis">${diffTitleHtml}</div>`;
        newRowNode.children[1].innerHTML = `<div class="neo-rename--dialog-diff-ellipsis">${diffPathRelHtml}</div>`;

        if (selectTool.value === "single") {
            newRowNode.children[0].innerHTML += `<div class="neo-rename--dialog-diff-ellipsis neo-rename--dialog-diff-ellipsis-copy-for-width">${diffTitleHtml}</div>`;
            newRowNode.children[1].innerHTML += `<div class="neo-rename--dialog-diff-ellipsis neo-rename--dialog-diff-ellipsis-copy-for-width">${diffPathRelHtml}</div>`;
        }

        newRowNode.children[0].setAttribute("title", row.title   + " ➔ " + row.newTitle);
        newRowNode.children[1].setAttribute("title", row.pathRel + " ➔ " + row.newPathRel);

        newRowNode.querySelectorAll("ins, del").forEach(node => node.removeAttribute("style"));

        newRowNode.classList.toggle("neo-rename--dialog-table-row-excluded", row.excluded);

        const excludeActionButtonNode = newRowNode.querySelector(".neo-rename--dialog-action-button-exclude"), excludeActionLabel = row.excluded ? neo__("Include in rename", "In Umbenennung einschließen") : neo__("Exclude from rename", "Vom Umbenennen ausschließen");
        excludeActionButtonNode.setAttribute("aria-label", excludeActionLabel); excludeActionButtonNode.querySelector("img").src = `${pluginUrl()}/_global-lucide-icons-thirdparty/${row.excluded ? "list-plus" : "list-x"}.svg`; excludeActionButtonNode.nextElementSibling.textContent = excludeActionLabel;

        newRowNode.querySelector(".neo-rename--dialog-rename-status-button").setAttribute("data-neo-rename--status", row.renameStatus ?? "");

        newRowNode.classList.remove("neo-rename--dialog-table-row-even", "neo-rename--dialog-table-row-odd"); newRowNode.classList.add((index + 1) % 2 === 0 ? "neo-rename--dialog-table-row-even" : "neo-rename--dialog-table-row-odd");

        newRowNode.setAttribute("data-neo-rename--img-url", row.imgUrl);

        newRowNode.addEventListener("pointerenter", (evt) => {
            if (getState() === "progress") { return; }
            showPreviewImage(evt.currentTarget.getAttribute("data-neo-rename--img-url"));
        });

        newRowNode.addEventListener("click", async (evt) => {
            if (getState() === "progress") { return; }
            if (evt.target.closest(".neo-rename--dialog-action-button-exclude, .neo-rename--dialog-rename-status-button, .neo-rename--dialog-action-button-preview")) { return; }
            openRenameDialog.lastSelectionChangedDate ??= 0; openRenameDialog.selectionChangeEventListenerCreated ??= false; if (!openRenameDialog.selectionChangeEventListenerCreated) { openRenameDialog.selectionChangeEventListenerCreated = true; document.addEventListener("selectionchange", () => { if (!window.getSelection().toString()) { return; } openRenameDialog.lastSelectionChangedDate = Date.now(); }); }
            if ((Date.now() - openRenameDialog.lastSelectionChangedDate) / 1000 < (0.15)) { return; }
            await delay(0.15);
            if (!window.getSelection().isCollapsed) { return; }
            mediaPreviewPopup.open(newRowNode.getAttribute("data-neo-rename--img-url"));
        });

        newRowNode.querySelector(".neo-rename--dialog-action-button-preview").addEventListener("click", (evt) => {
            if (getState() === "progress") { return; }
            mediaPreviewPopup.open(newRowNode.getAttribute("data-neo-rename--img-url"));
        });

        newRowNode.querySelector(".neo-rename--dialog-action-button-exclude").addEventListener("click", async (evt) => {
            const imgUrl = newRowNode.getAttribute("data-neo-rename--img-url");
            for (const row of rows) { if (row.imgUrl === imgUrl) { row.excluded = !row.excluded; break; } }
            await generateNewTitlesAndPathRelsAndRender();
        });
        return newRowNode;
    });
    const getBulkModeEmptyStateText = () => {
        switch (selectTool.value) {
            case "find-replace":     return neo__("No matches found for the current search or no changes.",                      "Keine Treffer für die aktuelle Suche gefunden oder keine Änderung.");
            case "prepend":          return neo__("No matches found for the current search or no prefix was specified.",         "Keine Treffer für die aktuelle Suche gefunden oder kein Präfix angegeben.");
            case "append":           return neo__("No matches found for the current search or no suffix was specified.",         "Keine Treffer für die aktuelle Suche gefunden oder kein Suffix angegeben.");
            case "new-name":         return neo__("No matches found for the current search or no changes.",                      "Keine Treffer für die aktuelle Suche gefunden oder keine Änderung.");
            case "remove":           return neo__("No matches found for the current search or no text to remove was specified.", "Keine Treffer für die aktuelle Suche gefunden oder kein Text zum Entfernen angegeben.");
            case "clean":            return neo__("All found media files already comply with the naming rules.",                 "Alle gefundenen Mediendateien entsprechen bereits den Namensregeln.");
            case "remove-subfolder": return neo__("None of the found files are located in a subfolder.",                         "Keine der gefundenen Dateien befindet sich in einem Unterordner.");
            case "derive-title":     return neo__("Titles have already been derived from the filenames.",                        "Die Titel sind bereits aus den Dateinamen abgeleitet.");
            case "derive-filename":  return neo__("Filenames have already been derived from the titles.",                        "Die Dateinamen sind bereits aus den Titeln abgeleitet.");
            case "undo":             return neo__("There are no recent renaming actions to undo.",                               "Es gibt keine kürzlich durchgeführten Umbenennungen zum Rückgängigmachen.");
            default: return null;
        }
    };

    const updateRowRendering = () => {
        tableEmptyState.innerText = viewableRowsInTable.length === 0 ? getBulkModeEmptyStateText() : "";

        let footerText = "";
        if (!(getState() === "progress")) {
            const numberOfRowsFiltered = rows.filter(row => row.isFilterMatching).length;
            const numberOfRowsExcluded = rows.filter(row => row.isFilterMatching && row.excluded).length;
            const numberOfRowsUnchangedButNotExcluded = checkboxHideUnchanged.checked ? rows.filter(row => row.isFilterMatching && !row.excluded && rowHasNoChanges(row)).length : 0;
            if (numberOfRowsFiltered > 0) {
                footerText = neo__("Results: ", "Ergebnisse: ") + (numberOfRowsFiltered - numberOfRowsExcluded - numberOfRowsUnchangedButNotExcluded);
                if (numberOfRowsExcluded > 0)                { footerText += " · " + neo__("Excluded: ", "Ausgeschlossen: ") + numberOfRowsExcluded; }
                if (numberOfRowsUnchangedButNotExcluded > 0) { footerText += " · " + neo__("Unchanged: ", "Unverändert: ")   + numberOfRowsUnchangedButNotExcluded; }
                footerText += " · " + neo__("Total: ", "Gesamt: ") + rows.length;
            } else {
                footerText = neo__("No results", "Keine Ergebnisse");
            }
                 if (getState() === "done")  { footerText += " · " + neo__("Renaming done ✓", "Umbenennen abgeschlossen ✓"); }
            else if (getState() === "error") { footerText += " · " + (rows.some(row => row.renameStatus === "error") ? neo__("Renaming failed ✕", "Umbenennen fehlgeschlagen ✕") : neo__("Renaming canceled ✕", "Umbenennen abgebrochen ✕")); }
        } else {
            const numberOfRowsWaiting  = rows.filter(row => row.isFilterMatching && row.renameStatus === "waiting").length;
            const numberOfRowsProgress = rows.filter(row => row.isFilterMatching && row.renameStatus === "progress").length;
            const numberOfRowsSuccess  = rows.filter(row => row.isFilterMatching && row.renameStatus === "success").length;
            const numberOfRowsError    = rows.filter(row => row.isFilterMatching && row.renameStatus === "error").length;
            const numberOfRowsSkipped  = rows.filter(row => row.isFilterMatching && row.renameStatus === "skipped").length;
            const numberOfRowsTotal    = numberOfRowsWaiting + numberOfRowsProgress + numberOfRowsSuccess + numberOfRowsError + numberOfRowsSkipped;
            const numberOfRowsDone     = numberOfRowsTotal - numberOfRowsWaiting - numberOfRowsProgress;
            footerText = neo__("Completed: ", "Erledigt: ") + numberOfRowsDone;
            if (numberOfRowsError   > 0) { footerText += " · " + neo__("Errors: ", "Fehler: ") + numberOfRowsError;; }
            if (numberOfRowsSkipped > 0) { footerText += " · " + neo__("Skipped: ", "Übersprungen: ") + numberOfRowsSkipped; }
            footerText += " · " + neo__("Total: ", "Gesamt: ") + numberOfRowsTotal;
        }
        dialog.querySelector("#neo-rename--dialog-table-footer-text").innerText = footerText;

        updateRowRendering.lastNumberOfRows ??= viewableRowsInTable.length;
        if (updateRowRendering.lastNumberOfRows !== viewableRowsInTable.length) {
            rerenderList();
            updateRowRendering.lastNumberOfRows = viewableRowsInTable.length;
        } else {
            updateRowData();
        }
    };
    updateRowRendering();

    const _getSuggestions = (searchStr) => {
        searchStr = searchStr.toLowerCase();
        const mediaItemTitles = mediaItems.map(item => item.title).sort(); const mediaItemTitlesLowercase = mediaItemTitles.map(t => t.toLowerCase());

        const displays = []; const freqs = []; const segLens = []; const keys = []; const keyToIndex = new Map();
        for (let i = 0; i < mediaItemTitles.length; i++) {
            if (!mediaItemTitlesLowercase[i].startsWith(searchStr)) { continue; }
            const segsLowercase = mediaItemTitlesLowercase[i].split(" ").filter(Boolean); const segsOrig = mediaItemTitles[i].split(" ").filter(Boolean);
            let cumulativeStr = ""; let cumulativeDisp = "";
            for (let j = 0; j < segsLowercase.length; j++) {
                cumulativeStr  = j ? cumulativeStr  + " " + segsLowercase[j] : segsLowercase[0];
                cumulativeDisp = j ? cumulativeDisp + " " + segsOrig[j]      : segsOrig[0];
                const index = keyToIndex.get(cumulativeStr) ?? -1;
                if (cumulativeStr === searchStr) { continue; }
                if (index >= 0) { freqs[index]++ } else { keyToIndex.set(cumulativeStr, keys.length), keys.push(cumulativeStr), displays.push(cumulativeDisp), freqs.push(1), segLens.push(j + 1); }
            }
        }

        const list = []; for (let k = 0; k < displays.length; k++) if (freqs[k] >= 2) { list.push({ display: displays[k], freq: freqs[k], segments: segLens[k] }); }
        list.sort((a, b) => (a.segments - b.segments) || (b.freq - a.freq) || a.display.toLowerCase().localeCompare(b.display.toLowerCase()));
        return list.slice(0, isSafari() ? (256) : (8)).map(x => x.display);
    };

    function updateTitleSuggestionList() {
        suggestionList.innerHTML = "";
        if (window.innerWidth < (768)) { titleInput.removeAttribute("list"); return; } else { titleInput.setAttribute("list", "neo-rename--dialog-suggestion-list"); }
        const suggestions = _getSuggestions(titleInput.value.trim());
        for (const suggestion of suggestions) {
            const suggestionItem = document.createElement("option"); suggestionItem.value = suggestion; suggestionItem.innerText = suggestion;
            suggestionList.appendChild(suggestionItem);
        }
    }
    titleInput.addEventListener("input", () => updateTitleSuggestionList());
    window.addEventListener("resize", updateTitleSuggestionList);
    updateTitleSuggestionList();

    async function generateNewTitlesAndPathRelsAndRender() {
        generateNewTitlesAndPathRelsAndRender.lastCalledDate = Date.now(); if (generateNewTitlesAndPathRelsAndRender.isCalling) { return; } generateNewTitlesAndPathRelsAndRender.isCalling = true; while ((Date.now() - generateNewTitlesAndPathRelsAndRender.lastCalledDate) / 1000 < (mediaItems.length * (1.0) / (100000))) { await new Promise(requestAnimationFrame); } generateNewTitlesAndPathRelsAndRender.isCalling = false;

        const filterText = filterInput.value.trim();
        const filterRegex = createRegexFromSearchOrRegexString(filterText, selectFilter.value);

        for (const row of rows) { row.isFilterMatching = false; }
        const exactFilterMatchingRow = selectFilter.value === "search" ? rows.find(row => row.pathRel === filterText || stripProtocol(row.imgUrl) === stripProtocol(filterText)) : null;
        if (exactFilterMatchingRow) { exactFilterMatchingRow.isFilterMatching = true; }
        else {
            for (const row of rows) {
                if (row.title.match(filterRegex) !== null || row.pathRel.match(filterRegex) !== null) {
                    row.isFilterMatching = true;
                    if (selectTool.value === "single") { break; }
                }
            }
        }
        if (!filterText && selectTool.value !== "single") { for (const row of rows) { row.isFilterMatching = true; } }

        for (const row of rows) {
            row.newTitle = row.title; row.newPathRel = row.pathRel; row.newSlug = row.slug; row.newAltText = row.altText ?? "";
            if (!row.isFilterMatching) { continue; }
            row.newPathRel = row.newPathRel.replace(fileExtensionRegex, "");
            switch (selectTool.value) {
                case "single":
                    row.newTitle = titleInput.value; row.newPathRel = titleInput.value;
                break;
                case "find-replace":
                    if (!filterText)       { break; }
                    if (!titleInput.value) { break; }
                    row.newTitle   = replaceWithoutEmptyMatch(row.newTitle,   filterRegex, titleInput.value);
                    row.newPathRel = replaceWithoutEmptyMatch(row.newPathRel, filterRegex, titleInput.value);
                break;
                case "prepend":
                    const dir  = row.newPathRel.includes("/") ? row.newPathRel.replace(/\/[^\/]*$/, "") : "";
                    const stem = row.newPathRel.split("/").pop();
                    row.newTitle   = titleInput.value + row.title;
                    row.newPathRel = (dir ? dir + "/" : "") + titleInput.value + stem;
                break;
                case "append":
                    row.newTitle = row.newTitle + titleInput.value; row.newPathRel = row.newPathRel + titleInput.value;
                break;
                case "new-name":
                    row.newTitle = titleInput.value; row.newPathRel = titleInput.value;
                break;
                case "remove":
                    row.newTitle = row.title.replace(titleInput.value ? createRegexFromSearchOrRegexString(titleInput.value, selectFilter.value) : filterRegex, ""); row.newPathRel = row.newPathRel.replace(titleInput.value ? createRegexFromSearchOrRegexString(titleInput.value, selectFilter.value) : filterRegex, "");
                break;
                case "clean":
                    row.newTitle = row.title; row.newPathRel = row.pathRel;
                    row.newTitle = cleanTitle(row.newTitle, true); row.newPathRel = cleanPathRel(row.newPathRel);
                break;
            }

            if (["remove-subfolder", "derive-title", "derive-filename", "undo"].includes(selectTool.value)) {
                row.newTitle = neo__("Get Pro", "Pro holen"); row.newPathRel = "get-pro"; row.newSlug = "get-pro";
            }

            if (selectTool.value === "derive-filename") { row.newPathRel = row.newPathRel.replaceAll("/", "-"); }

            if (selectTool.value !== "undo") {
                row.newTitle = row.newTitle.trim(); row.newPathRel = row.newPathRel.trim();

                if (row.newPathRel !== row.pathRel.replace(fileExtensionRegex, "") && !["remove", "remove-subfolder"].includes(selectTool.value)) { row.newPathRel = cleanPathRel(row.newPathRel); }

                if (!row.newTitle.trim())                    { row.newTitle   = "Untitled"; }
                if (!row.newPathRel.split("/").pop().trim()) { row.newPathRel = "untitled"; }

                let fileExtension = row.pathRel.match(fileExtensionRegex)?.[0]?.toLowerCase();
                if (fileExtension) { row.newPathRel += fileExtension; }

                if (["single", "new-name", "derive-filename"].includes(selectTool.value)) {
                    if (row.pathRel.includes("/") && !row.newPathRel.includes("/")) { row.newPathRel = row.pathRel.replace(/\/[^\/]*$/, "") + "/" + row.newPathRel; }
                }

                if (selectTool.value === "clean") { row.newPathRel = row.newPathRel.replace(/\.jpeg$/i, ".jpg"); }
            }

            if (selectTool.value !== "undo") { row.newSlug = row.newPathRel.split("/").pop().replace(fileExtensionRegex, ""); }

            if (!checkboxTitle.checked)    { row.newTitle = row.title; }
            if (!checkboxFilename.checked) { row.newPathRel = row.pathRel; row.newSlug = row.slug; }
            if (selectTool.value === "single") { row.newAltText = altTextInput.value.trim(); }
        }

        const slugSet = new Set(rows.map(row => row.slug));
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].newSlug === rows[i].slug || rows[i].excluded) { continue; }
            let targetSlug = rows[i].newSlug;
            let counterSuffix = null;
            while (slugSet.has(targetSlug)) {
                counterSuffix = (counterSuffix ?? 0) + 1;
                targetSlug = `${rows[i].newSlug}-${counterSuffix}`;
            }
            rows[i].newSlug = targetSlug;
            slugSet.delete(rows[i].slug);
            slugSet.add(rows[i].newSlug);
        }

        const pathRelSet = new Set(rows.flatMap(row => [row.pathRel, ...(row.originalPathRel ? [row.originalPathRel] : [])]));
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].newPathRel === rows[i].pathRel || rows[i].excluded) { continue; }
            let targetPathRel = rows[i].newPathRel;
            let counterSuffix = null;
            while (pathRelSet.has(targetPathRel)) {
                counterSuffix = (counterSuffix ?? 0) + 1;
                const fileWithoutExtension = rows[i].newPathRel.replace(fileExtensionRegex, "");
                const fileExtension = rows[i].newPathRel.match(fileExtensionRegex)?.[0] || "";
                targetPathRel = `${fileWithoutExtension}-${counterSuffix}${fileExtension}`;
            }
            rows[i].newPathRel = targetPathRel;
            pathRelSet.delete(rows[i].pathRel);
            pathRelSet.add(rows[i].newPathRel);
        }

        updateViewableRowsInTable();
        if (selectTool.value === "single" && viewableRowsInTable.length === 1 && viewableRowsInTable[0].pathRel !== lastAltTextSyncedRowPathRel) { lastAltTextSyncedRowPathRel = viewableRowsInTable[0].pathRel; suppressInputChanged = true; altTextProgrammaticInput = true; altTextInput.value = getSingleInputTextValues(viewableRowsInTable[0]).altText; altTextInput.dispatchEvent(new Event("input")); altTextProgrammaticInput = false; suppressInputChanged = false; setAltTextAiUndoState(null); }
        if (selectTool.value !== "single") { lastAltTextSyncedRowPathRel = null; }
        if (selectTool.value === "single" && singleNavigationRenameFlow.targetImgUrl) { showPreviewImageLoadingPlaceholder(); } else { showPreviewImage(viewableRowsInTable[0]?.imgUrl ?? null); }

        updateTitleSuggestionList();

        updateRowRendering();

        updateInputDisabledStates();
        updateRenameButtonState();
    }

    let altTextBeforeAi = null; let altTextProgrammaticInput = false; let titleProgrammaticInput = false;
    const setAltTextAiUndoState = (previousAltText) => { altTextBeforeAi = previousAltText; buttonAltTextUndo.classList.toggle("neo-rename--dialog-alt-text-button-hidden", previousAltText === null); updateInputDisabledStates(); };
    setAltTextAiUndoState(null);
    let lastAltTextSyncedRowPathRel = null;

    filterInput.addEventListener("input",            async (evt) => { await generateNewTitlesAndPathRelsAndRender(); });
    titleInput.addEventListener("input",             async (evt) => { await generateNewTitlesAndPathRelsAndRender(); });
    selectFilter.addEventListener("change",          async (evt) => { await generateNewTitlesAndPathRelsAndRender(); });
    selectTool.addEventListener("change",            async (evt) => { await generateNewTitlesAndPathRelsAndRender(); });
    checkboxTitle.addEventListener("change",         async (evt) => { await generateNewTitlesAndPathRelsAndRender(); });
    checkboxFilename.addEventListener("change",      async (evt) => { await generateNewTitlesAndPathRelsAndRender(); });
    checkboxHideUnchanged.addEventListener("change", async (evt) => {
        let visibleRowIndex = getFirstFullyVisibleRowIndex();
        let rowIndexOfRowWithChanges = null;
        for (let i = visibleRowIndex; i < viewableRowsInTable.length; i++) {
            if (!viewableRowsInTable[i].excluded && !rowHasNoChanges(viewableRowsInTable[i])) {
                rowIndexOfRowWithChanges = i;
                break;
            }
        }
        if (rowIndexOfRowWithChanges === null) {
            for (let i = visibleRowIndex - 1; i >= 0; i--) {
                if (!viewableRowsInTable[i].excluded && !rowHasNoChanges(viewableRowsInTable[i])) {
                    rowIndexOfRowWithChanges = i;
                    break;
                }
            }
        }

        const anchorRowPathRel = rowIndexOfRowWithChanges !== null ? viewableRowsInTable[rowIndexOfRowWithChanges].pathRel : null;

        await generateNewTitlesAndPathRelsAndRender();

        if (anchorRowPathRel) {
            const rowsInTableAfter = viewableRowsInTable;
            const newAnchorRowIndex = anchorRowPathRel ? rowsInTableAfter.findIndex(row => row.pathRel === anchorRowPathRel) : null;
            await new Promise(requestAnimationFrame);
            scrollToRowIndex(newAnchorRowIndex);
        }
    });
    await generateNewTitlesAndPathRelsAndRender();

    async function syncSingleDialogInputsToRow(row) {
        const { title, altText } = getSingleInputTextValues(row);
        currentNavigationImgUrl = row.imgUrl;
        suppressInputChanged = true; filterInput.value = row.pathRel; titleInput.value = title; altTextProgrammaticInput = true; altTextInput.value = altText;
        filterInput.dispatchEvent(new Event("input")); titleInput.dispatchEvent(new Event("input")); altTextInput.dispatchEvent(new Event("input")); setSingleNavigationInputBaseline(); updateRenameButtonState(); altTextProgrammaticInput = false; suppressInputChanged = false; inputChanged = false;
        setAltTextAiUndoState(null); await generateNewTitlesAndPathRelsAndRender(); updateAltTextInputLayout(); updateInputDisabledStates();
    }
    async function navigateSingleDialogToImgUrl(imgUrl) {
        const targetRow = rows.find(row => stripProtocol(row.imgUrl) === stripProtocol(imgUrl));
        if (!targetRow) { return; }
        await syncSingleDialogInputsToRow(targetRow);
        titleInput.focus(); titleInput.select();
    }
    async function navigateSingleDialog(direction) {
        const currentNavigationIndex = getSingleNavigationIndex(); const targetImgUrl = singleNavigationImgUrls[currentNavigationIndex + direction];
        if (getState() !== "edit" || selectTool.value !== "single" || !targetImgUrl) { return; }
        const currentSingleRow = viewableRowsInTable.length === 1 ? viewableRowsInTable[0] : null;
        if (currentSingleRow && !singleNavigationInputBaseline && filterInput.value === "" && titleInput.value === "") { await syncSingleDialogInputsToRow(currentSingleRow); }
        if (currentSingleRow && inputChanged && !singleNavigationInputMatchesBaseline()) {
            const { renameChanged, altTextChanged } = getSingleActionChangeState();
            const navigationConfirmText       = renameChanged && altTextChanged ? neo__("Rename & save before switching to the next image?", "Vor dem Wechsel zum nächsten Bild umbenennen & speichern?") : (altTextChanged ? neo__("Save before switching to the next image?", "Vor dem Wechsel zum nächsten Bild speichern?") : neo__("Rename before switching to the next image?", "Vor dem Wechsel zum nächsten Bild umbenennen?"));
            const navigationConfirmButtonText = renameChanged && altTextChanged ? neo__("Rename, save & continue", "Umbenennen, speichern & weiter") : (altTextChanged ? neo__("Save & continue", "Speichern & weiter") : neo__("Rename & continue", "Umbenennen & weiter"));
            const navigationConfirmResult = await Swal.fire({ customClass: { container: "neo-rename--dialog-swal" }, icon: "question", title: neo__("Unsaved changes", "Ungespeicherte Änderungen"), text: navigationConfirmText, showDenyButton: true, showCancelButton: false, allowOutsideClick: false, confirmButtonText: navigationConfirmButtonText, denyButtonText: neo__("Discard", "Verwerfen") });
            if (navigationConfirmResult.isConfirmed) { singleNavigationRenameFlow.keepDialogOpen = true; singleNavigationRenameFlow.targetImgUrl = targetImgUrl; buttonRename.click(); return; }
            if (!navigationConfirmResult.isDenied) { return; }
        }
        singleNavigationRenameFlow.keepDialogOpen = true;
        await navigateSingleDialogToImgUrl(targetImgUrl);
    }
    buttonNavigationPrevious.addEventListener("click", async () => { await navigateSingleDialog(-1); });
    buttonNavigationNext.addEventListener("click", async () => { await navigateSingleDialog(1); });

    addEventListenerWithInitialCall(selectFilter, "change", () => {
        switch (selectFilter.value) {
            case "search": buttonExplainFilter.innerHTML = `<h3>${neo__("Search", "Suchen")}</h3><ul><li>${neo__("Direct text search in <strong>filename, title and slug</strong>", "Direkte Textsuche in <strong>Dateiname, Titel und Slug</strong>")}</li><li>${neo__("Ignores <strong>uppercase and lowercase</strong>", "Ignoriert <strong>Groß- und Kleinschreibung</strong>")}</li><li>${neo__("Changes are only applied to media found through the <strong>search field</strong>", "Änderungen werden nur auf durch das <strong>Suchfeld</strong> gefundene Medien angewendet")}</li></ul>`; break;
            case "regex":  buttonExplainFilter.innerHTML = `<h3>${neo__("Regex", "Regex")}</h3><ul><li>${neo__("Regex-based search in <strong>filename, title and slug</strong>", "Regex-basierte Suche in <strong>Dateiname, Titel und Slug</strong>")}</li><li>${neo__("Precise <strong>search and replace rules</strong> for complex patterns", "Präzise <strong>Such- und Ersetzregeln</strong> für komplexe Muster")}</li><li>${neo__("Swapping or rebuilding <strong>text patterns</strong> is possible", "Vertauschen oder Neuaufbauen von <strong>Textmustern</strong> möglich")}</li><li>${neo__("Accepts formats like <strong>/.../flags</strong> or a direct pattern", "Akzeptiert Formate wie <strong>/.../flags</strong> oder direktes Muster")}</li><li>${neo__("Changes are only applied to media found through the <strong>search field</strong>", "Änderungen werden nur auf durch das <strong>Suchfeld</strong> gefundene Medien angewendet")}</li><li>${neo__('Regex examples at <a href="https://neo-wp.com/plugin/neo-rename/#regex" rel="noopener" target="_blank"><strong>neo-wp.com/plugin/neo-rename/#regex</strong></a>', 'Regex-Beispiele auf <a href="https://neo-wp.com/plugin/neo-rename/#regex" rel="noopener" target="_blank"><strong>neo-wp.com/plugin/neo-rename/#regex</strong></a>')}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__("Search field: /(.*)-(\\d+)/<br>Replace field: $2-$1<br>Result: product-123.jpg → 123-product.jpg", "Suchfeld: /(.*)-(\\d+)/<br>Ersetzenfeld: $2-$1<br>Ergebnis: produkt-123.jpg → 123-produkt.jpg")}</em>`; break;
            default: throw new Error("Unknown filter mode selected when trying to update filter explain button");
        }
        buttonExplainFilter.innerHTML = `<div style="text-align: left;">${buttonExplainFilter.innerHTML}</div>`;
    });

    addEventListenerWithInitialCall(selectTool, "change", () => {
        switch (selectTool.value) {
            case "single":           buttonExplainTool.innerHTML = ""; break;
            case "find-replace":     buttonExplainTool.innerHTML = `<h3>${neo__("Search &amp; Replace", "Suchen &amp; Ersetzen")}</h3>             <ul><li>${neo__("Replaces <strong>one text</strong> with another", "Ersetzt <strong>einen Text</strong> durch einen anderen")}</li><li>${neo__("Optional in <strong>title, filename and slug</strong>", "Optional in <strong>Titel, Dateiname und Slug</strong>")}</li></ul>`; break;
            case "prepend":          buttonExplainTool.innerHTML = `<h3>${neo__("Prefix", "Präfix")}</h3>                                          <ul><li>${neo__("Adds text at the <strong>beginning</strong>", "Text am <strong>Anfang</strong> hinzufügen")}</li><li>${neo__("Only applied to media found through the <strong>search field</strong>", "Wird nur auf durch das <strong>Suchfeld</strong> gefundene Medien angewendet")}</li><li>${neo__("Useful for <strong>categories</strong> or <strong>project abbreviations</strong>", "Praktisch für <strong>Kategorien</strong> oder <strong>Projektkürzel</strong>")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__('"product-" → oak-chair.jpg becomes product-oak-chair.jpg', '"produkt-" → stuhl-eiche.jpg wird zu produkt-stuhl-eiche.jpg')}</em>`; break;
            case "append":           buttonExplainTool.innerHTML = `<h3>${neo__("Suffix", "Suffix")}</h3>                                          <ul><li>${neo__("Adds text at the <strong>end</strong>", "Text am <strong>Ende</strong> hinzufügen")}</li><li>${neo__("Only applied to media found through the <strong>search field</strong>", "Wird nur auf durch das <strong>Suchfeld</strong> gefundene Medien angewendet")}</li><li>${neo__("Useful for <strong>versions</strong> or <strong>additional labels</strong>", "Praktisch für <strong>Versionen</strong> oder <strong>ergänzende Bezeichnungen</strong>")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__('"-backup" → brochure.pdf becomes brochure-backup.pdf', '"-backup" → broschuere.pdf wird zu broschuere-backup.pdf')}</em>`; break;
            case "new-name":         buttonExplainTool.innerHTML = `<h3>${neo__("New Name", "Neuer Name")}</h3>                                    <ul><li>${neo__("Sets a <strong>completely new name</strong>", "Einen <strong>komplett neuen Namen</strong> setzen")}</li><li>${neo__("Only applied to media found through the <strong>search field</strong>", "Wird nur auf durch das <strong>Suchfeld</strong> gefundene Medien angewendet")}</li><li>${neo__("Useful for <strong>consistent naming</strong> across multiple files", "Praktisch für <strong>einheitliche Benennung</strong> über mehrere Dateien")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__('"hero-image" → banner-2026.jpg becomes hero-image.jpg', '"hero-image" → banner-2026.jpg wird zu hero-image.jpg')}</em>`; break;
            case "remove":           buttonExplainTool.innerHTML = `<h3>${neo__("Remove", "Entfernen")}</h3>                                       <ul><li>${neo__("Removes the <strong>specified text</strong>", "Angegebenen <strong>Text</strong> entfernen")}</li><li>${neo__("Only applied to media found through the <strong>search field</strong>", "Wird nur auf durch das <strong>Suchfeld</strong> gefundene Medien angewendet")}</li><li>${neo__("Useful for removing <strong>suffixes, numbers or old terms</strong>", "Nützlich zum Entfernen von <strong>Zusätzen, Nummern oder alten Begriffen</strong>")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__('Remove "-copy" → logo-copy.png becomes logo.png', 'Entferne "-kopie" → logo-kopie.png wird zu logo.png')}</em>`; break;
            case "clean":            buttonExplainTool.innerHTML = `<h3>${neo__("Clean", "Bereinigen")}</h3>                                       <ul><li>${neo__("Capitalizes <strong>titles</strong> intelligently", "Titel <strong>intelligent</strong> großschreiben")}</li><li>${neo__("Lowercases <strong>filenames</strong>", "<strong>Dateinamen</strong> kleinschreiben")}</li><li>${neo__("Replaces <strong>special characters</strong>", "<strong>Sonderzeichen</strong> ersetzen (inkl. Umlaute)")}</li><li>${neo__("Converts <strong>emojis</strong> into plain text", "<strong>Emojis</strong> in Klartext übertragen")}</li><li>${neo__("Standardizes the <strong>.jpeg</strong> extension to <strong>.jpg</strong>", "Dateiendung <strong>.jpeg</strong> zu <strong>.jpg</strong> vereinheitlichen")}</li><li>${neo__("Only applied to media found through the <strong>search field</strong>", "Wird nur auf durch das <strong>Suchfeld</strong> gefundene Medien angewendet")}</li><li>${neo__("With an empty search field, <strong>all media</strong> is cleaned", "Mit leerem Suchfeld werden <strong>alle Medien</strong> bereinigt")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__('Title: "title of image" → "Title of Image"<br>Filename: "File_Name.jpg" → "file-name.jpg"', 'Titel: "titel des bilds" → "Titel Des Bilds"<br>Dateiname: "Datei_Name.jpg" → "datei-name.jpg"')}</em>`; break;
            case "remove-subfolder": buttonExplainTool.innerHTML = `<h3>${neo__("Remove Subfolder", "Unterordner entfernen")}</h3>                 <ul><li>${neo__("Removes <strong>subfolders</strong> and moves files into the <strong>uploads root directory</strong>", "Entfernt <strong>Unterordner</strong> und verschiebt Dateien ins <strong>Uploads-Überverzeichnis</strong>")}</li><li>${neo__("Disables <strong>date-based upload folders</strong> for future uploads", "Deaktiviert <strong>datumsbasierte Upload Ordner</strong> für zukünftige Uploads")}</li><li>${neo__("Ideal for <strong>cleaning up media folders</strong> and <strong>standardizing the structure</strong>", "Ideal zum <strong>Aufräumen von Medienordnern</strong> und <strong>Vereinheitlichen der Struktur</strong>")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__("2025/12/image.jpg → image.jpg", "2025/12/image.jpg → image.jpg")}</em>`; break;
            case "derive-title":     buttonExplainTool.innerHTML = `<h3>${neo__("Derive Title from Filename", "Titel aus Dateiname ableiten")}</h3><ul><li>${neo__("Turns the <strong>filename</strong> into a <strong>clean title</strong>", "Den <strong>Dateinamen</strong> in einen <strong>sauberen Titel</strong> umwandeln")}</li><li>${neo__("Formats the title into a <strong>readable form</strong>", "Titel wird <strong>lesbar formatiert</strong>")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__("chatgpt-api-guide.pdf → ChatGPT API Guide", "chatgpt-api-guide.pdf → ChatGPT API Guide")}</em>`; break;
            case "derive-filename":  buttonExplainTool.innerHTML = `<h3>${neo__("Derive Filename from Title", "Dateiname aus Titel ableiten")}</h3><ul><li>${neo__("Turns the <strong>title</strong> into a <strong>usable filename</strong>", "Den <strong>Titel</strong> in einen <strong>brauchbaren Dateinamen</strong> umwandeln")}</li><li>${neo__("Creates an <strong>SEO-friendly filename</strong>", "<strong>SEO freundlicher Dateiname</strong> wird erstellt")}</li><li>${neo__("Removes <strong>special characters</strong>", "<strong>Sonderzeichen</strong> werden entfernt")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__('"Cafe in München 🎉" → cafe-in-muenchen-party-popper.jpg', '"Café in München 🎉" → cafe-in-muenchen-party-popper.jpg')}</em>`; break;
            case "undo":             buttonExplainTool.innerHTML = `<h3>${neo__("Undo", "Rückgängig")}</h3>                                        <ul><li>${neo__("Reverts <strong>previous renames</strong> and <strong>alt text changes</strong>", "<strong>Frühere Umbenennungen</strong> und <strong>Alt-Text Änderungen</strong> rückgängig machen")}</li><li>${neo__("Restores <strong>title, filename, slug and alt text</strong>", "<strong>Titel, Dateiname, Slug und Alt-Text</strong> werden wiederhergestellt")}</li><li>${neo__("Only applied to media found through the <strong>search field</strong>", "Wird nur auf durch das <strong>Suchfeld</strong> gefundene Medien angewendet")}</li><li>${neo__("Restores the <strong>latest rename</strong> for each file", "Es wird jeweils die <strong>letzte Umbenennung</strong> pro Datei wiederhergestellt")}</li></ul><h4>${neo__("Example", "Beispiel")}</h4><em>${neo__("new-name.jpg → old-name.jpg", "neuer-name.jpg → alter-name.jpg")}</em>`; break;
            default: throw new Error("Unknown tool selected when trying to update mode explain button");
        }
        if (buttonExplainTool.innerHTML) { buttonExplainTool.innerHTML = `<div style="text-align: left;">${buttonExplainTool.innerHTML}</div>`; }
    });

    addEventListenerWithInitialCall(selectTool, "change", () => {
        checkboxTitle.checked = checkboxFilename.checked = true;
        disableCheckboxesTitleAndFilenameBecauseOfTool = false;
        switch (selectTool.value) {
            case "remove-subfolder": checkboxFilename.checked = true;  checkboxTitle.checked = false; disableCheckboxesTitleAndFilenameBecauseOfTool = true; break;
            case "derive-title":     checkboxFilename.checked = false; checkboxTitle.checked = true;  disableCheckboxesTitleAndFilenameBecauseOfTool = true; break;
            case "derive-filename":  checkboxFilename.checked = true;  checkboxTitle.checked = false; disableCheckboxesTitleAndFilenameBecauseOfTool = true; break;
        }
        updateInputDisabledStates();
        checkboxTitle.dispatchEvent(new Event("change")); checkboxFilename.dispatchEvent(new Event("change"));

        checkboxHideUnchanged.checked = ["clean", "remove-subfolder", "undo"].includes(selectTool.value); updateViewableRowsInTable();

        labelTitle.innerText = neo__("Title", "Titel"); labelFilename.innerText = neo__("Filename", "Dateiname");
        switch (selectTool.value) {
            case "clean":           labelTitle.innerText    = neo__("Cleaned title", "Bereinigter Titel"); labelFilename.innerText = neo__("Cleaned filename", "Bereinigter Dateiname"); break;
            case "derive-title":    labelTitle.innerText    = neo__("Derived title", "Abgeleiteter Titel");                                                                              break;
            case "derive-filename": labelFilename.innerText = neo__("Derived filename", "Abgeleiteter Dateiname");                                                                       break;
        }
    });

    let lastTitleInputValue = ""; addEventListenerWithInitialCall(titleInput, "input", () => { lastTitleInputValue = titleInput.value; });
    addEventListenerWithInitialCall(selectTool, "change", async () => {
        if (["clean", "remove-subfolder", "derive-title", "derive-filename", "undo"].includes(selectTool.value)) {
            titleInput.value = ""; titleInput.dispatchEvent(new Event("input")); disableNameInputBecauseOfTool = true; updateInputDisabledStates(); updateTitleSuggestionList(); await generateNewTitlesAndPathRelsAndRender();
        } else {
            disableNameInputBecauseOfTool = false; titleInput.value = lastTitleInputValue; titleInput.dispatchEvent(new Event("input")); updateInputDisabledStates();
        }
        let nextTitleInputPlaceholder = "";
        switch (selectTool.value) {
            case "single":           nextTitleInputPlaceholder = neo__("New image title...", "Neuer Bildtitel...");                     break;
            case "find-replace":     nextTitleInputPlaceholder = neo__("Replace...", "Ersetzen...");                                    break;
            case "prepend":          nextTitleInputPlaceholder = neo__("Text to prepend...", "Text zum Voranstellen...");               break;
            case "append":           nextTitleInputPlaceholder = neo__("Text to append...", "Text zum Anhängen...");                    break;
            case "new-name":         nextTitleInputPlaceholder = neo__("New image title...", "Neuer Bildtitel...");                     break;
            case "remove":           nextTitleInputPlaceholder = neo__("Text to remove...", "Text, der entfernt werden soll...");       break;
            case "clean":            nextTitleInputPlaceholder = neo__("➔ Clean", "➔ Bereinigen");                                      break;
            case "remove-subfolder": nextTitleInputPlaceholder = neo__("➔ Remove Subfolder", "➔ Unterordner entfernen");                break;
            case "derive-title":     nextTitleInputPlaceholder = neo__("➔ Derive Title", "➔ Titel ableiten");                           break;
            case "derive-filename":  nextTitleInputPlaceholder = neo__("➔ Derive Filename", "➔ Dateinamen ableiten");                   break;
            case "undo":             nextTitleInputPlaceholder = neo__("➔ Undo Changes", "➔ Änderungen rückgängig machen");             break;
            default: throw new Error("Unknown tool selected when trying to update placeholder");
        }
        if (titleInputRow) { titleInputRow.setAttribute("data-neo-rename--placeholder", nextTitleInputPlaceholder); }
    });

    let filterInputForRestoreWhenSwitchingToSingleMode = "";
    let filterInputForRestoreWhenSwitchingToBulkMode   = "";

    selectTool.addEventListener("change", async () => {
        if (selectTool.value === "single") { filterInputForRestoreWhenSwitchingToBulkMode   = filterInput.value; }
        else                               { filterInputForRestoreWhenSwitchingToSingleMode = filterInput.value; }
        switch (selectTool.value) {
            case "single":       filterInput.value = filterInputForRestoreWhenSwitchingToSingleMode; break;
            case "find-replace": filterInput.value = filterInputForRestoreWhenSwitchingToBulkMode;   break;
            case "prepend":      filterInput.value = filterInputForRestoreWhenSwitchingToBulkMode;   break;
            case "append":       filterInput.value = filterInputForRestoreWhenSwitchingToBulkMode;   break;
            case "new-name":     filterInput.value = filterInputForRestoreWhenSwitchingToBulkMode;   break;
            case "remove":       filterInput.value = filterInputForRestoreWhenSwitchingToBulkMode;   break;
            case "clean":                                                                            break;
            case "remove-subfolder":                                                                 break;
            case "derive-title":                                                                     break;
            case "derive-filename":                                                                  break;
            case "undo":                                                                             break;
            default: throw new Error("Unknown tool selected");
        }
        if (selectTool.value === "single" && viewableRowsInTable.length >= 1) { filterInput.value = viewableRowsInTable[0].pathRel; }
        suppressInputChanged = true; filterInput.dispatchEvent(new Event("input")); suppressInputChanged = false;
        await generateNewTitlesAndPathRelsAndRender();
        if (selectTool.value === "single" && viewableRowsInTable.length === 1) {
            await syncSingleDialogInputsToRow(viewableRowsInTable[0]);
        }
    });

    filterInput.addEventListener("input", () => { if (selectTool.value !== "single") { filterInputForRestoreWhenSwitchingToBulkMode = filterInput.value; } });

    observeClick(buttonFilterClear, (evt) => {
        filterInput.value = "";
        filterInput.dispatchEvent(new Event("input"));
        updateInputDisabledStates();
    });
    observeClick(buttonUndoClean, (evt) => {
        titleInput.value = viewableRowsInTable[0]?.title || "";
        titleInput.dispatchEvent(new Event("input"));
        updateInputDisabledStates();
    });

    altTextInput.addEventListener("input", async () => { const row = getCurrentSingleRow(); if (!altTextProgrammaticInput && row) { (await neoLoadInterfaceFunc("neo-rename", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: row.imgUrl, textType: "alt" }); setAltTextAiUndoState(null); } await generateNewTitlesAndPathRelsAndRender(); });
    altTextInput.addEventListener("blur", () => { altTextInput.scrollTop = 0; });
    const generateTitleWithAi = async () => {
        const row = getCurrentSingleRow();
        if (!row) { return; }
        setAiGenerationState({ fieldNode: titleInputRow, buttonNode: buttonTitleAi, generating: true }); updateInputDisabledStates();
        try {
            const nearbyTitlesForAi = (() => {
                const navigationRows = singleNavigationImgUrls.map(imgUrl => rows.find(row => stripProtocol(row.imgUrl) === stripProtocol(imgUrl))).filter(Boolean);
                const currentIndex = navigationRows.findIndex(navigationRow => stripProtocol(navigationRow.imgUrl) === stripProtocol(row.imgUrl));
                if (currentIndex < 0) { return []; }
                let newerRows = navigationRows.slice(Math.max(0, currentIndex - 5), currentIndex); let olderRows = navigationRows.slice(currentIndex + 1, currentIndex + 6);
                if (newerRows.length < 5) { olderRows = navigationRows.slice(currentIndex + 1, currentIndex + 1 + 10 - newerRows.length); }
                if (olderRows.length < 5) { newerRows = navigationRows.slice(Math.max(0, currentIndex - (10 - olderRows.length)), currentIndex); }
                return [...newerRows, ...olderRows].map(nearbyRow => nearbyRow.title).filter(title => title && title !== row.title).slice(0, 10);
            })();
            const generatedTitle = await (await neoLoadInterfaceFunc("neo-rename", "neo-ai--image-text-generation.js", "interfaceGenerateImageText20260713"))({ imageUrl: row.imgUrl, textType: "title", swalContainerClass: "neo-rename--dialog-swal", nearbyTitles: nearbyTitlesForAi, imageTitle: titleInput.value.trim(), imageAltText: altTextInput.value.trim() });
            if (generatedTitle === null) { return; }
            titleProgrammaticInput = true; titleInput.value = generatedTitle; titleInput.dispatchEvent(new Event("input")); titleProgrammaticInput = false;
            updateInputDisabledStates();
        } finally {
            setAiGenerationState({ fieldNode: titleInputRow, buttonNode: buttonTitleAi, generating: false }); updateInputDisabledStates();
        }
    };
    observeClick(buttonClear, (evt) => {
        titleInput.value = "";
        titleInput.dispatchEvent(new Event("input"));
        updateInputDisabledStates();
    });
    observeClick(buttonTitleAi, async () => { await generateTitleWithAi(); });
    observeClick(buttonAltTextAi, async () => {
        const row = getCurrentSingleRow();
        if (!row) { return; }
        setAiGenerationState({ fieldNode: altTextInputWrapper, buttonNode: buttonAltTextAi, generating: true }); updateInputDisabledStates();
        try {
            const previousAltText = altTextBeforeAi ?? altTextInput.value;
            const generatedAltText = await (await neoLoadInterfaceFunc("neo-rename", "neo-ai--image-text-generation.js", "interfaceGenerateImageText20260713"))({ imageUrl: row.imgUrl, textType: "alt", swalContainerClass: "neo-rename--dialog-swal", imageTitle: titleInput.value.trim(), imageAltText: altTextInput.value.trim() });
            if (generatedAltText === null) { return; }
            setAltTextAiUndoState(previousAltText);
            altTextProgrammaticInput = true; altTextInput.value = generatedAltText; altTextInput.dispatchEvent(new Event("input")); altTextProgrammaticInput = false;
        } finally {
            setAiGenerationState({ fieldNode: altTextInputWrapper, buttonNode: buttonAltTextAi, generating: false }); updateInputDisabledStates();
        }
    });
    observeClick(buttonAltTextUndo, async () => {
        if (altTextBeforeAi === null) { return; }
        const previousAltText = altTextBeforeAi;
        const row = getCurrentSingleRow(); if (row) { (await neoLoadInterfaceFunc("neo-rename", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: row.imgUrl, textType: "alt" }); } setAltTextAiUndoState(null);
        altTextProgrammaticInput = true; altTextInput.value = previousAltText; altTextInput.dispatchEvent(new Event("input")); altTextProgrammaticInput = false;
    });

    let dynamicInputFieldWidthFlag = false;
    addEventListenerWithInitialCallMultiple([[filterInput, "input"], [titleInput, "input"]], async () => {
        if (!dynamicInputFieldWidthFlag) { await new Promise(requestAnimationFrame); dynamicInputFieldWidthFlag = true; }
        filterInput.style.width = titleInput.style.width = "1px";
        const newWidth = Math.max((300), Math.max(filterInput.scrollWidth, titleInput.scrollWidth) + (60));
        filterInput.style.width = newWidth + "px"; titleInput.style.width = newWidth + "px";
    });

    let resolveAbortRenaming = null; function abortRenaming() { if (resolveAbortRenaming) { throw new Error("Cannot call abortRenaming() twice."); } return new Promise(resolve => resolveAbortRenaming = resolve).then(() => resolveAbortRenaming = null); }
    let pauseBulkRenaming = false;
    observeClick(buttonRename, async (node, evt) => {
        evt.preventDefault();

        if (getState() === "error" || getState() === "done") { closeDialog(); return; }

        const rowsToRename = viewableRowsInTable.filter(row => !row.excluded && !rowHasNoChanges(row));

        if (selectTool.value !== "single" && rowsToRename.length > 1) {
            const confirmResult = await Swal.fire({
                customClass: { container: "neo-rename--dialog-swal" },
                icon: "question",
                title: neo__(`Rename ${rowsToRename.length} images?`, `${rowsToRename.length} Bild${rowsToRename.length !== 1 ? "er" : ""} umbenennen?`),
                html: neo__(`Are you sure you want to rename <strong>${rowsToRename.length}</strong> images?`, `Möchtest du wirklich <strong>${rowsToRename.length}</strong> Bild${rowsToRename.length !== 1 ? "er" : ""} umbenennen?`),
                showCancelButton: true,
                confirmButtonText: neo__("Rename all", "Alle umbenennen"), cancelButtonText: neo__("Cancel", "Abbrechen"),
            });
            if (!confirmResult.isConfirmed) { singleNavigationRenameFlow.targetImgUrl = null; return; }
        }

        setState("progress"); updateRowRendering();

        fetchEndpoint("/wp-json/neo/rename-dialog-log-state", {
            method: "POST", body: { state: {
                filterInput:            filterInput.value,
                selectFilter:           selectFilter.value,
                titleInput:             titleInput.value,
                selectTool:             selectTool.value,
                checkboxTitle:          checkboxTitle.checked,
                checkboxFilename:       checkboxFilename.checked,
                checkboxHideUnchanged:  checkboxHideUnchanged.checked,
            } }
        });

        if (selectTool.value === "remove-subfolder") {
            if ((await fetchEndpoint("/wp-json/neo/rename-dialog-date-upload-folder-setting-enabled").then(extractJson).catch(err => { neoError("Error checking date upload folder setting:", err); return { value: true }; })).value) {
                const mediaSettingsUrl = jsVar("neoRenameMediaSettingsUrl");
                const confirmResult = await Swal.fire({
                    customClass: { container: "neo-rename--dialog-swal" },
                    title: neo__("Disable subfolders", "Unterordner deaktivieren"),
                    html: neo__("To prevent creating subfolders for future uploads, the option <a href=\"" + mediaSettingsUrl + "\" target=\"_blank\">\"Organize my uploads into month- and year-based folders\"</a> will be disabled in the WP settings.", "Um auch zukünftig beim Upload keine Unterordner anzulegen, wird die Option <a href=\"" + mediaSettingsUrl + "\" target=\"_blank\">„Uploads nach Jahr und Monat sortieren“</a> in den WP Einstellungen deaktiviert."),
                    icon: "question", showCancelButton: true, focusCancel: true,
                    confirmButtonText: neo__("Disable subfolders", "Unterordner deaktivieren"), cancelButtonText: neo__("Cancel", "Abbrechen"),
                });
                if (!confirmResult.isConfirmed) {
                    singleNavigationRenameFlow.targetImgUrl = null; setState("edit"); updateRowRendering();
                    return;
                }

                try {
                    await fetchEndpoint("/wp-json/neo/rename-dialog-disable-date-upload-folder-setting", { method: "POST" }).then(extractJson);
                } catch (error) {
                    if (error.message && error.message.includes("need admin permissions")) {
                        neoError("Error updating date upload folder setting due to insufficient permissions:", error);
                        Swal.fire({
                            customClass: { container: "neo-rename--dialog-swal" },
                            title: neo__("Insufficient Permissions", "Fehlende Berechtigung"),
                            html: neo__("You need administrator permissions to change the WordPress setting. Please contact your site administrator.", "Du benötigst Administratorrechte, um die WordPress-Einstellung zu ändern. Bitte kontaktiere den Administrator deiner Website."),
                            icon: "error", confirmButtonText: neo__("OK", "OK"),
                        });
                        singleNavigationRenameFlow.targetImgUrl = null; setState("edit"); updateRowRendering();
                        return;
                    }
                    neoError("Error updating date upload folder setting:", error);
                    Swal.fire({
                        customClass: { container: "neo-rename--dialog-swal" },
                        title: neo__("Error", "Fehler"),
                        html: neo__("There was an error updating the WordPress setting. Perhaps you are missing the required permissions.", "Beim Aktualisieren der WordPress-Einstellung ist ein Fehler aufgetreten. Möglicherweise fehlen Zugriffsrechte."),
                        icon: "error", confirmButtonText: neo__("OK", "OK"),
                    });
                    singleNavigationRenameFlow.targetImgUrl = null; setState("edit"); updateRowRendering();
                    return;
                }
            }
        }

        if (await isPagebuilderOpen()) {
            const response = await Swal.fire({
                customClass: { container: "neo-rename--dialog-swal" },
                title: neo__("Pagebuilder Open", "Pagebuilder geöffnet"),
                html: neo__("Please close all Pagebuilders and Edit-Tabs to avoid issues.", "Bitte schließe alle Pagebuilder und Edit-Tabs, um Probleme zu vermeiden."),
                icon: "warning",
                showCancelButton: true, cancelButtonText: neo__("Cancel", "Abbrechen"),
                confirmButtonText: neo__("Continue", "Fortfahren"),
                focusConfirm: true,
            });
            if (!response.isConfirmed) {
                singleNavigationRenameFlow.targetImgUrl = null; setState("edit"); updateRowRendering();
                return;
            }
        }
        if (selectTool.value === "single" && singleNavigationRenameFlow.keepDialogOpen) { showPreviewImageLoadingPlaceholder(); }

        for (const row of viewableRowsInTable) {
            row.renameStatus = "waiting";
        }

        progressBar.style.setProperty("--progress", 0.0001);
        let lastUserScrollTimestamp = 0; addEventListenerWithInitialCallMultiple([[tableScrollWrapper, "wheel"], [tableScrollWrapper, "touchmove"], [tableScrollWrapper, "mousedown"]], () => lastUserScrollTimestamp = Date.now());
        let lastAutoScrollTimestamp = 0;
        updateViewableRowsInTable();
        for (let rowIndex = 0; rowIndex < viewableRowsInTable.length; rowIndex++) {
            while (pauseBulkRenaming) { await new Promise(requestAnimationFrame); }
            if (resolveAbortRenaming) { resolveAbortRenaming(); break; }
            const row = viewableRowsInTable[rowIndex];

            function updateRowStatus(newStatus) { row.renameStatus = newStatus; updateRowRendering(); }
            updateRowStatus("progress");

            const rowScrollTop = rowIndex * rowHeight;
            const rowIsNotWithinInnerThird = rowScrollTop + rowHeight <= tableScrollWrapper.scrollTop + tableScrollWrapper.clientHeight / 3 || rowScrollTop >= tableScrollWrapper.scrollTop + tableScrollWrapper.clientHeight * (2 / 3);
            const scrollCooldownReached = Date.now() - lastUserScrollTimestamp > (2) * 1000 && Date.now() - lastAutoScrollTimestamp > (0.25) * 1000;
            if (rowIsNotWithinInnerThird && scrollCooldownReached) {
                tableScrollWrapper.scrollTo({ top: rowScrollTop - tableScrollWrapper.clientHeight / 3 + rowHeight / 2, behavior: "smooth" });
                lastAutoScrollTimestamp = Date.now();
            }

            if (row.excluded || rowHasNoChanges(row)) {
                updateRowStatus("skipped");
                await new Promise(requestAnimationFrame);
                continue;
            }

            if (!(selectTool.value === "single" && singleNavigationRenameFlow.targetImgUrl)) { showPreviewImage(row.imgUrl); }

            try {
                let renameResponse; if (window.neoRenameFakeMode) { renameResponse = await delay(0.1).then(() => ({ imgUrl: row.imgUrl.endsWith(row.pathRel) ? row.imgUrl.slice(0, -row.pathRel.length) + row.newPathRel : row.imgUrl, title: row.newTitle, pathRel: row.newPathRel, slug: row.newSlug, altText: row.newAltText })); } else { renameResponse = await fetchEndpoint("/wp-json/neo/rename", { method: "POST", body: { "img-url": row.imgUrl, "path-rel": row.newPathRel, "slug": row.newSlug, "title": row.newTitle, "alt-text": row.newAltText } }).then(extractJson); }
                updateRowStatus("success");
                try { await onUpdateCallback({ oldImgUrl: row.imgUrl, oldTitle: row.title, oldPathRel: row.pathRel, oldSlug: row.slug, oldAltText: row.altText, newImgUrl: renameResponse.imgUrl, newTitle: renameResponse.title, newPathRel: renameResponse.pathRel, newSlug: renameResponse.slug, newAltText: renameResponse.altText }); } catch (callbackError) { neoError(callbackError); }
                row.newTitle = renameResponse.title; row.newPathRel = renameResponse.pathRel; row.newSlug = renameResponse.slug; row.newAltText = renameResponse.altText ?? ""; row.altText = row.newAltText;
                if (selectTool.value === "single") { row.title = renameResponse.title; row.pathRel = renameResponse.pathRel; row.slug = renameResponse.slug; }
                const navigationIndexAfterRename = getSingleNavigationIndex(); if (navigationIndexAfterRename >= 0) { singleNavigationImgUrls[navigationIndexAfterRename] = renameResponse.imgUrl; currentNavigationImgUrl = renameResponse.imgUrl; }
                row.imgUrl = renameResponse.imgUrl;
                if (selectTool.value === "single") { filterInput.value = renameResponse.pathRel; titleInput.value = renameResponse.title; altTextInput.value = renameResponse.altText ?? ""; setSingleNavigationInputBaseline(); updateRenameButtonState(); inputChanged = false; }
            } catch (err) {
                neoError(err);
                singleNavigationRenameFlow.targetImgUrl = null;
                row.renameStatus = "error";
                const dummy = document.createElement("div"); dummy.innerText = `${row.title} ➔ ${row.newTitle}, ${row.pathRel} ➔ ${row.newPathRel}`; const escapedChangesHtml = dummy.innerHTML;
                dummy.innerText = String(err?.message ?? neo__("Unknown error.", "Unbekannter Fehler.")).split(/\sSQL (?:command|Befehl):\s/u, 1)[0]; const escapedErrorHtml = dummy.innerHTML;
                const errorDialogResponse = await Swal.fire({
                    icon: "error",
                    customClass: { container: "neo-rename--dialog-swal" },
                    title: neo__("Error", "Fehler"), html: neo__("Could not rename image!", "Konnte Bild nicht umbenennen!") + "<br>" + escapedChangesHtml + "<br>" + escapedErrorHtml,
                    ...selectTool.value === "single" ? {} : { confirmButtonText: neo__("Continue renaming", "Umbenennen fortsetzen"), showCancelButton: true, cancelButtonText: neo__("Stop renaming", "Umbenennen stoppen") },
                    ...selectTool.value !== "single" ? {} : { showConfirmButton: false,                                               showCancelButton: true, cancelButtonText: neo__("OK", "OK") }
                });
                if (!errorDialogResponse.isConfirmed) {
                    setState("error"); updateRowRendering();
                    renameReject(err);
                    return;
                }
            } finally {
                progressBar.style.setProperty("--progress", rowsToRename.filter(row => ["success", "error", "skipped"].includes(row.renameStatus)).length / rowsToRename.length);
            }
            if (resolveAbortRenaming) { resolveAbortRenaming(); break; }
        }

        setState("done"); updateRowRendering(); dialog.dispatchEvent(new CustomEvent("neoRenameDialogRenamingDone", { bubbles: true }));

        if (!resolveAbortRenaming) {
            if (selectTool.value === "single" && singleNavigationRenameFlow.keepDialogOpen) {
                inputChanged = false; if (singleNavigationRenameFlow.targetImgUrl) { showPreviewImageLoadingPlaceholder(); } else { showPreviewImage(viewableRowsInTable[0]?.imgUrl ?? null); }
                const buttonRenameTextNode = buttonRename.querySelector(".neo-rename--dialog-rename-button-text");
                buttonRename.classList.remove("neo-rename--loading"); buttonRename.classList.add("neo-rename--done"); buttonRenameTextNode.innerText = neo__("Done", "Fertig");
                await delay(2);
                if (!dialog.isConnected) { return; }
                buttonRename.classList.remove("neo-rename--done");
                for (const row of rows) { row.renameStatus = null; }
                setState("edit"); await generateNewTitlesAndPathRelsAndRender();
                if (singleNavigationRenameFlow.targetImgUrl) { const targetImgUrl = singleNavigationRenameFlow.targetImgUrl; singleNavigationRenameFlow.targetImgUrl = null; await navigateSingleDialogToImgUrl(targetImgUrl); }
            } else if (selectTool.value === "single") { closeDialog(); }
            else {
                showPreviewImage("done");
                const numberOfRowsDone = rows.filter(row => ["success", "error"].includes(row.renameStatus)).length;
                const numberOfRowsError = rows.filter(row => row.renameStatus === "error").length;
                const successDialogResponse = await Swal.fire({
                    icon: "success",
                    customClass: { container: "neo-rename--dialog-swal" },
                    title: neo__("Renaming successful", "Umbenennen erfolgreich"), text: (numberOfRowsError === 0) ? neo__(`All ${numberOfRowsDone} images renamed successfully!`, `Alle ${numberOfRowsDone} Bilder erfolgreich umbenannt!`) : neo__(`Renaming successful. However, ${numberOfRowsError} images could not be renamed.`, `Umbenennen erfolgreich. Allerdings konnten ${numberOfRowsError} Bilder nicht umbenannt werden.`),
                    confirmButtonText: neo__("OK", "OK"),
                });
                dialog.dispatchEvent(new CustomEvent("neoRenameDialogSuccessConfirmed", { bubbles: true }));
            }
        }

        buttonClose.focus();
    });

    const onKeyPress = (evt) => {
        if (Swal.isVisible()) { return; }
             if (evt.key === "Escape" && !mediaPreviewPopup.hasAttribute("open"))                                                                                                                     { closeDialog();        evt.preventDefault(); evt.stopImmediatePropagation(); }
        else if (evt.key === "Enter" && selectTool.value === "single" && !(["button", "select", "textarea", "neo-select-neo-rename", "neo-info-tooltip-neo-rename"].includes(document.activeElement.tagName.toLowerCase()))) { buttonRename.click(); evt.preventDefault(); evt.stopImmediatePropagation(); }
    };
    window.addEventListener("keydown", onKeyPress);

    inputChanged = false; filterInput.addEventListener("input", () => { if (!suppressInputChanged) { inputChanged = true; } }); titleInput.addEventListener("input", async () => { const row = getCurrentSingleRow(); if (!suppressInputChanged) { inputChanged = true; } if (!titleProgrammaticInput && !suppressInputChanged && row) { (await neoLoadInterfaceFunc("neo-rename", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: row.imgUrl, textType: "title" }); } }); altTextInput.addEventListener("input", () => { if (!suppressInputChanged) { inputChanged = true; } });
    async function closeDialog() {
        if (getState() === "progress") {
            if (resolveAbortRenaming) { return; }
            pauseBulkRenaming = true;
            const result = await Swal.fire({
                customClass: { container: "neo-rename--dialog-swal" },
                title: neo__("Stop renaming?", "Umbenennen stoppen?"),
                html: neo__("Do you really want to stop renaming?", "Möchtest du das Umbenennen wirklich stoppen?"),
                icon: "warning", showCancelButton: true,
                confirmButtonText: neo__("Stop renaming", "Umbenennen stoppen"), cancelButtonText: neo__("Continue", "Fortsetzen"),
                confirmButtonColor: "#DC3545",
                cancelButtonColor: "#2A78C1",
            });
            pauseBulkRenaming = false;
            if (!result.isConfirmed) { return; }
            await abortRenaming();
            setState("error"); updateRowRendering();
            return;
        }

        if (inputChanged && !["done", "error"].includes(getState())) {
            const result = await Swal.fire({
                customClass: { container: "neo-rename--dialog-swal" },
                title: neo__("Close Dialog?", "Dialog schließen?"),
                html: neo__("Do you really want to close the rename dialog?", "Möchtest du den Dialog zum Umbenennen wirklich schließen?"),
                icon: "warning", showCancelButton: true, focusConfirm: true,
                confirmButtonText: neo__("Yes, close dialog", "Ja, Dialog schließen"), cancelButtonText: neo__("Cancel", "Abbrechen"),
            });
            if (!result.isConfirmed) { return; }
        }

        dialog.remove();
        window.removeEventListener("keydown", onKeyPress);
        window.removeEventListener("resize", updateTitleSuggestionList);
        renameResolve();
    }
    buttonClose.addEventListener("click", closeDialog);

    dialog.addEventListener("pointerdown", async () => {
        closeDialog();
    });
    dialog.querySelectorAll(".neo-rename--dialog-box>*").forEach(el => el.addEventListener("pointerdown", (evt) => { evt.stopPropagation(); }));

    setState("edit"); updateRowRendering();

    if (selectTool.value === "single" || filterInputText) {
        titleInput.focus();
        titleInput.select();
    } else {
        filterInput.focus();
    }

    document.dispatchEvent(new CustomEvent("neoRenameDialogOpened", { detail: { dialog, rows } }));

    return renamedPromise;
}

export async function interfaceOpenRenameDialog20250813({ filterInputText = "", inputMode = "single", onUpdateCallback = () => {}, onlyIncludeImgUrls = null, navigationImgUrls = null, initialTextValuesByImgUrl = {} }) { return openRenameDialog({ filterInputText, inputMode, onUpdateCallback, onlyIncludeImgUrls, navigationImgUrls, initialTextValuesByImgUrl }) }
