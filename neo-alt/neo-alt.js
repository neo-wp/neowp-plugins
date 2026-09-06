import { infiniteVirtualScroll } from "./_global--scroll-infinite-virtual.js";
import { matchesSearchText } from "./_global-search.js";

import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";
import { addCacheBust, fitProtocolToFetchImgUrl } from "./_global--url-helper.js";
import { getFileType } from "./_global-media-file-type.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { DomNodeHelper, escapeHtml } from "./_global--dom-node-helper.js";
import { neo__ } from "./_global--translation.js";
import Swal from "./_global-sweetalert2.js";
import { setAiGenerationState } from "./_global--ai-generation-state.js";
import { addEventListenerWithInitialCallMultiple, domLoaded } from "./_global--observer.js";

import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { isModuleAvailable } from "./_global--interface.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { neoError } from "./_global--log.js";

await domLoaded();
const fieldOrigins = { saved: "saved", manual: "manual", ai: "ai" }; const itemOperations = { idle: "idle", saving: "saving", generatingTitle: "generating-title", generatingAlt: "generating-alt" }; const pageOperations = { idle: "idle", generating: "generating", cancellingGeneration: "cancelling-generation", acceptingDrafts: "accepting-drafts", discardingDrafts: "discarding-drafts" };
const mediaItems = JSON.parse(document.querySelector("#neo-alt--media-data").textContent).map(({ title, altText, ...item }) => ({ ...item, fields: { title: { value: title, initialValue: title, savedValue: title, origin: fieldOrigins.saved }, alt: { value: altText, initialValue: altText, savedValue: altText, origin: fieldOrigins.saved, width: "", height: "" } }, operation: itemOperations.idle, queuedGenerationTypes: [], error: "" }));
const mediaItemsById = new Map(mediaItems.map(item => [String(item.id), item]));
const mediaItemsByUploadDate = [...mediaItems].sort((itemA, itemB) => itemB.uploadDate.localeCompare(itemA.uploadDate));
const selectedMediaItemIds = new Set(mediaItems.map(item => String(item.id)));
const tableContainerNode        = document.querySelector("#neo-alt--table-container");
const tableNode                 = document.querySelector("#neo-alt--table");
const bulkSelectionCheckboxNode = document.querySelector("#neo-alt--bulk-selection-checkbox");
const searchInputNode           = document.querySelector("#neo-alt--search-input");
const searchClearNode           = document.querySelector("#neo-alt--search-clear");
const emptyFilterNode           = document.querySelector("#neo-alt--empty-filter");
const sortSelectNode            = document.querySelector("#neo-alt--sort-select");
const bulkGenerateButtonNode    = document.querySelector("#neo-alt--bulk-generate-button");
const bulkProgressNode          = document.querySelector("#neo-alt--bulk-progress");
const bulkCancelButtonNode      = document.querySelector("#neo-alt--bulk-cancel-button");
const acceptAllButtonNode       = document.querySelector("#neo-alt--accept-all-button");
const discardAllButtonNode      = document.querySelector("#neo-alt--discard-all-button");
const searchTooltipNode         = document.querySelector("#neo-alt--search-tooltip");
const emptyFilterTooltipNode    = document.querySelector("#neo-alt--empty-filter-tooltip");
const previewPopupNode          = document.querySelector("#neo-alt--preview-popup");
let filteredMediaItems = [...mediaItems];
let selectedBulkMode = "alt";
let bulkPromptAddition = "";
let pageOperation = pageOperations.idle;
let bulkSelectionAnchor = null;
let exitIntentNextAllowedTimestamp = 0; let exitIntentRelevantStateExisted = false; let exitIntentDialogOpen = false; let exitIntentClosedForHiddenTab = false;
let previousPointerPosition = null; let lastPointerPosition = null; let lastPointerMoveTimestamp = 0;
const mobileAltInputMediaQuery = window.matchMedia("(max-width: 767px)");
let lastUserScrollTimestamp = 0; let lastAutoScrollTimestamp = 0; addEventListenerWithInitialCallMultiple([[window, "wheel"], [window, "touchmove"], [window, "mousedown"]], () => lastUserScrollTimestamp = Date.now());
document.addEventListener("mousemove", event => { previousPointerPosition = lastPointerPosition; lastPointerPosition = { x: event.clientX, y: event.clientY }; lastPointerMoveTimestamp = Date.now(); }, { passive: true });

function getBulkGenerationTypes(item) {
    const fileType = getFileType(item.imgUrl);
    if (!(["image", "video", "pdf", "txt"].includes(fileType))) { return []; }
    if (selectedBulkMode === "title" || fileType !== "image") { return selectedBulkMode === "alt" ? [] : ["title"]; }
    return selectedBulkMode === "both" ? ["title", "alt"] : ["alt"];
}

document.querySelector("#neo-alt--feedback-button")?.addEventListener("click", async () => { await (await neoLoadInterfaceFunc("neo-alt", "neo-feedback.js", "interfaceOpenFeedbackDialog20260802"))(); });

function mediaItemDiffersFromInitial(item) { return Object.values(item.fields).some(fieldState => fieldState.value !== fieldState.initialValue); }

function fieldDiffersFromSaved(item, field) { return item.fields[field].value !== item.fields[field].savedValue; }

function mediaItemHasDraft(item) { return fieldDiffersFromSaved(item, "title") || fieldDiffersFromSaved(item, "alt"); }

function syncFieldCounters(rowNode, item) {
    const titleCounterNode = rowNode.querySelector(".neo-alt--title-counter"); const titleLength = Array.from(item.fields.title.value).length; titleCounterNode.textContent = titleLength; titleCounterNode.hidden = titleLength === 0;
    const altCounterNode = rowNode.querySelector(".neo-alt--alt-counter");
    if (!altCounterNode) { return; }
    const altTextLength = Array.from(item.fields.alt.value).length; altCounterNode.closest(".neo-alt--alt-counter-tooltip").hidden = altTextLength === 0; altCounterNode.textContent = altTextLength + "/125"; altCounterNode.classList.toggle("neo-alt--alt-counter-exceeded", altTextLength < 80 || altTextLength >= 150);
}

function syncAltInputLayout(rowNode, item) {
    const altInputNode = rowNode.querySelector(".neo-alt--alt-input");
    if (!(altInputNode && altInputNode.isConnected)) { return; }
    const inputStyle = getComputedStyle(altInputNode); const inputLineHeight = parseFloat(inputStyle.lineHeight); const canvasContext = document.createElement("canvas").getContext("2d"); canvasContext.font = inputStyle.font;
    const displayedAltText = altInputNode.value || altInputNode.placeholder;
    const contentWidth = Math.max(...displayedAltText.split("\n").map(line => canvasContext.measureText(line || " ").width));
    altInputNode.style.width = Math.ceil(contentWidth + parseFloat(inputStyle.paddingLeft) + parseFloat(inputStyle.paddingRight) + 1) + "px";
    altInputNode.style.height = "auto";
    altInputNode.style.height = Math.max(inputLineHeight, Math.min((mobileAltInputMediaQuery.matches ? 3.5 : 2.5) * inputLineHeight, altInputNode.scrollHeight)) + "px";
    item.fields.alt.width = altInputNode.style.width; item.fields.alt.height = altInputNode.style.height;
}

async function saveMediaItem(item, fields) {
    if (fields.includes("title") && item.fields.title.value.trim() === "") { item.fields.title.value = "Untitled"; item.fields.title.origin = fieldDiffersFromSaved(item, "title") ? fieldOrigins.manual : fieldOrigins.saved; }
    const saveFields = fields.filter(field => fieldDiffersFromSaved(item, field));
    if (saveFields.length === 0) { await updateUi(true); return true; }
    if (item.operation !== itemOperations.idle) { return false; }
    item.operation = itemOperations.saving;
    try {
        await updateUi(true);
        const requestItem = { "media-id": item.id, fields: saveFields }; if (saveFields.includes("title")) { requestItem.title = item.fields.title.value; } if (saveFields.includes("alt")) { requestItem["alt-text"] = item.fields.alt.value; }
        const response = await fetchEndpoint("/wp-json/neo/alt-save", { method: "POST", body: { items: [requestItem] } }).then(extractJson); const savedItem = response.items[0];
        if (saveFields.includes("title")) { item.fields.title.value = savedItem.title;   item.fields.title.savedValue = savedItem.title; item.fields.title.origin = fieldOrigins.saved; (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: item.imgUrl, textType: "title" }); }
        if (saveFields.includes("alt"))   { item.fields.alt.value   = savedItem.altText; item.fields.alt.savedValue = savedItem.altText; item.fields.alt.origin   = fieldOrigins.saved; (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: item.imgUrl, textType: "alt" }); }
        if (saveFields.includes("alt") && emptyFilterNode.value === "empty") { await applyFiltersAndSort(); }
        return true;
    } catch (error) { neoError("neoAlt single save failed:", error); await Swal.fire({ icon: "error", title: neo__("Could not save changes", "Änderungen konnten nicht gespeichert werden"), text: error?.message || neo__("Could not save changes.", "Änderungen konnten nicht gespeichert werden.") }); return false; }
    finally { item.operation = itemOperations.idle; await updateUi(true); }
}

const compactRowsMediaQuery = window.matchMedia("(max-width: 1280px)");
const virtualScroll = infiniteVirtualScroll(tableNode, index => index < filteredMediaItems.length ? (compactRowsMediaQuery.matches ? 144 : 128) : undefined, index => {
    const item = filteredMediaItems[index];
    const titleLength = Array.from(item.fields.title.value).length;
    const altTextLength = Array.from(item.fields.alt.value).length;
    const fileType = getFileType(item.imgUrl); const previewUrl = fitProtocolToFetchImgUrl(addCacheBust(item.thumbnailUrl || item.imgUrl, item.modifiedDate));
    const rowSaving = item.operation === itemOperations.saving; const generatingType = item.operation === itemOperations.generatingTitle ? "title" : (item.operation === itemOperations.generatingAlt ? "alt" : null); const rowBusyAttribute = item.operation === itemOperations.idle && pageOperation === pageOperations.idle ? "" : " disabled"; const reviewActionsVisible = mediaItems.some(mediaItemHasDraft) && !(pageOperation === pageOperations.generating || pageOperation === pageOperations.cancellingGeneration);
    const titleGenerationQueued = item.queuedGenerationTypes.includes("title"); const altGenerationQueued = item.queuedGenerationTypes.includes("alt");
    const placeholderByType = { audio: "_global-placeholder-audio-icon.svg", pdf: "_global-placeholder-pdf-icon.svg", txt: "_global-placeholder-txt-icon.svg", zip: "_global-placeholder-zip-icon.svg", other: "_global-placeholder-file-icon.svg" };
    const previewHtml = fileType === "image" ? `<img src="${escapeHtml(previewUrl)}" loading="lazy" fetchpriority="low" alt="${escapeHtml(item.fields.title.value.trim() || item.fields.title.initialValue)}">` : (fileType === "video" ? `<video src="${escapeHtml(previewUrl)}" preload="metadata" muted></video>` : `<img src="${escapeHtml(pluginUrl() + "/img/" + (placeholderByType[fileType] ?? placeholderByType.other))}" loading="lazy" alt="${escapeHtml(item.fields.title.value.trim() || item.fields.title.initialValue)}">`);
    const rowNode = new DomNodeHelper(`<div class="neo-alt--row${mediaItemDiffersFromInitial(item) ? " neo-alt--row-changed" : ""}${rowSaving ? " neo-alt--row-saving" : ""}" data-neo-alt--media-id="${item.id}">
        <button type="button" class="neo-alt--preview" aria-label="${escapeHtml(neo__("Open media preview", "Medienvorschau öffnen"))}">${previewHtml}</button>
        <div class="neo-alt--fields">
            <div class="neo-alt--field-line neo-alt--title-field-line"><div class="neo-alt--field neo-alt--title-field${item.fields.title.origin === fieldOrigins.ai ? " neo-alt--field-pending" : ""}${fieldDiffersFromSaved(item, "title") ? " neo-alt--field-draft" : ""}"><input type="text" class="neo-alt--title-input" value="${escapeHtml(item.fields.title.value)}" aria-label="${escapeHtml(neo__("Title", "Titel"))}"${rowBusyAttribute}><div class="neo-alt--field-options"><span class="neo-alt--field-counter neo-alt--title-counter">${titleLength}</span><neo-info-tooltip-neo-alt class="neo-alt--field-ai-tooltip" no-click-open instant-hover><button slot="icon" type="button" class="neo-alt--field-ai-button" data-neo-alt--generate-type="title"${titleGenerationQueued ? " data-neo-alt--generation-queued" : ""} aria-label="${escapeHtml(titleGenerationQueued ? neo__("Waiting for AI generation", "Wartet auf AI-Generierung") : neo__("Generate title with AI", "Titel mit AI generieren"))}"${rowBusyAttribute}><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/${titleGenerationQueued ? "hourglass" : "sparkles"}.svg" alt=""></button>${escapeHtml(titleGenerationQueued ? neo__("Waiting for AI generation", "Wartet auf AI-Generierung") : neo__("Generate title with AI", "Titel mit AI generieren"))}</neo-info-tooltip-neo-alt><neo-info-tooltip-neo-alt class="neo-alt--field-save-tooltip neo-alt--title-save-tooltip" no-click-open instant-hover${fieldDiffersFromSaved(item, "title") ? "" : " hidden"}><button slot="icon" type="button" class="neo-alt--field-save-button" data-neo-alt--save-field="title" aria-label="${escapeHtml(item.fields.title.origin === fieldOrigins.ai ? neo__("Accept and save AI title", "AI-Titel übernehmen und speichern") : neo__("Save title", "Titel speichern"))}"${rowBusyAttribute}><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/save.svg" alt=""></button><span class="neo-alt--field-save-label">${escapeHtml(item.fields.title.origin === fieldOrigins.ai ? neo__("Accept and save AI title", "AI-Titel übernehmen und speichern") : neo__("Save title", "Titel speichern"))}</span></neo-info-tooltip-neo-alt></div></div></div>
            ${fileType === "image" ? `<div class="neo-alt--field-line neo-alt--alt-field-line"><div class="neo-alt--field neo-alt--alt-field${item.fields.alt.origin === fieldOrigins.ai ? " neo-alt--field-pending" : ""}${fieldDiffersFromSaved(item, "alt") ? " neo-alt--field-draft" : ""}"><textarea class="neo-alt--alt-input" rows="1" aria-label="${escapeHtml(neo__("Alt text", "Alt-Text"))}"${rowBusyAttribute}>${escapeHtml(item.fields.alt.value)}</textarea><div class="neo-alt--field-options"><neo-info-tooltip-neo-alt class="neo-alt--alt-counter-tooltip" no-click-open instant-hover><span slot="icon" class="neo-alt--field-counter neo-alt--alt-counter${altTextLength < 80 || altTextLength >= 150 ? " neo-alt--alt-counter-exceeded" : ""}">${altTextLength}/125</span>${escapeHtml(neo__("Recommended: 80-125 characters, maximum 150", "Empfohlen: 80-125 Zeichen, maximal 150"))}</neo-info-tooltip-neo-alt><neo-info-tooltip-neo-alt class="neo-alt--field-ai-tooltip" no-click-open instant-hover><button slot="icon" type="button" class="neo-alt--field-ai-button" data-neo-alt--generate-type="alt"${altGenerationQueued ? " data-neo-alt--generation-queued" : ""} aria-label="${escapeHtml(altGenerationQueued ? neo__("Waiting for AI generation", "Wartet auf AI-Generierung") : neo__("Generate alt text with AI", "Alt-Text mit AI generieren"))}"${rowBusyAttribute}><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/${altGenerationQueued ? "hourglass" : "sparkles"}.svg" alt=""></button>${escapeHtml(altGenerationQueued ? neo__("Waiting for AI generation", "Wartet auf AI-Generierung") : neo__("Generate alt text with AI", "Alt-Text mit AI generieren"))}</neo-info-tooltip-neo-alt><neo-info-tooltip-neo-alt class="neo-alt--field-save-tooltip neo-alt--alt-save-tooltip" no-click-open instant-hover${fieldDiffersFromSaved(item, "alt") ? "" : " hidden"}><button slot="icon" type="button" class="neo-alt--field-save-button" data-neo-alt--save-field="alt" aria-label="${escapeHtml(item.fields.alt.origin === fieldOrigins.ai ? neo__("Accept and save AI alt text", "AI-Alt-Text übernehmen und speichern") : neo__("Save alt text", "Alt-Text speichern"))}"${rowBusyAttribute}><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/save.svg" alt=""></button><span class="neo-alt--field-save-label">${escapeHtml(item.fields.alt.origin === fieldOrigins.ai ? neo__("Accept and save AI alt text", "AI-Alt-Text übernehmen und speichern") : neo__("Save alt text", "Alt-Text speichern"))}</span></neo-info-tooltip-neo-alt></div></div></div>` : ""}
            <div class="neo-alt--filename-line"><button type="button" class="neo-alt--filename" title="${escapeHtml(item.filename)}" aria-label="${escapeHtml(neo__("Rename filename", "Dateiname umbenennen"))}"${rowBusyAttribute}>${escapeHtml(item.filename)}</button><neo-info-tooltip-neo-alt no-click-open instant-hover class="neo-alt--copy-tooltip"><button slot="icon" type="button" class="neo-alt--copy-button" aria-label="${escapeHtml(neo__("Copy URL", "URL kopieren"))}"${rowBusyAttribute}><img src="${pluginUrl()}/img/_global-button-link-icon.svg" alt=""></button><span class="neo-alt--copy-label">${escapeHtml(neo__("Copy URL", "URL kopieren"))}</span></neo-info-tooltip-neo-alt><span class="neo-alt--save-status"${rowSaving ? "" : " hidden"}>${escapeHtml(neo__("Saving...", "Wird gespeichert..."))}</span><span class="neo-alt--row-error" title="${escapeHtml(item.error)}"${item.error === "" ? " hidden" : ""}>${escapeHtml(item.error)}</span></div>
        </div>
        <div class="neo-alt--row-actions">
            <neo-info-tooltip-neo-alt no-click-open instant-hover class="neo-alt--undo-tooltip"><button slot="icon" type="button" class="neo-alt--undo-button" aria-label="${escapeHtml(neo__("Restore old texts", "Alte Texte wiederherstellen"))}"${rowBusyAttribute}><img src="${pluginUrl()}/_global-lucide-icons-thirdparty/undo-2.svg" alt=""></button>${escapeHtml(neo__("Restore old texts", "Alte Texte wiederherstellen"))}</neo-info-tooltip-neo-alt>
            <input type="checkbox" class="neo-alt--row-selection-checkbox" aria-label="${escapeHtml(neo__("Select for bulk generation", "Für Bulk-Generierung auswählen"))}"${selectedMediaItemIds.has(String(item.id)) ? " checked" : ""}${reviewActionsVisible ? " disabled" : ""}>
        </div>
    </div>`).getNode();
    rowNode.querySelector(".neo-alt--title-input").placeholder = neo__("Empty title", "Leerer Titel");
    if (fileType === "image") { rowNode.querySelector(".neo-alt--alt-input").placeholder = neo__("Empty alt text", "Leerer Alt-Text"); }
    if (generatingType) { setAiGenerationState({ fieldNode: rowNode.querySelector(`.neo-alt--${generatingType}-field`), buttonNode: rowNode.querySelector(`[data-neo-alt--generate-type="${generatingType}"]`), generating: true }); }
    requestAnimationFrame(() => syncAltInputLayout(rowNode, item));
    syncFieldCounters(rowNode, item);
    rowNode.querySelector(".neo-alt--preview").addEventListener("click", () => previewPopupNode.open(item.imgUrl));
    rowNode.querySelector(".neo-alt--filename").addEventListener("click", async () => {
        if (!isModuleAvailable("neo-rename")) {
            const confirmResult = await Swal.fire({ icon: "info", title: neo__("Install current neoRename", "Aktuelles neoRename installieren"), text: neo__("Install and activate the current version of neoRename to change filenames.", "Installiere und aktiviere die aktuelle Version von neoRename, um Dateinamen zu ändern."), showCancelButton: true, confirmButtonText: neo__("Open neoRename settings", "neoRename-Einstellungen öffnen"), cancelButtonText: neo__("Cancel", "Abbrechen") });
            if (!confirmResult.isConfirmed) { return; }
            window.open(jsVar("neoAltRenameSettingsPageUrl"), "_blank", "noopener"); return;
        }
        const navigationImgUrls = filteredMediaItems.map(navigationItem => navigationItem.imgUrl);
        const initialTextValuesByImgUrl = Object.fromEntries(mediaItems.filter(mediaItemHasDraft).map(draftItem => [draftItem.imgUrl, { ...(fieldDiffersFromSaved(draftItem, "title") ? { title: draftItem.fields.title.value } : {}), ...(fieldDiffersFromSaved(draftItem, "alt") ? { altText: draftItem.fields.alt.value } : {}) }]));
        await (await neoLoadInterfaceFunc("neo-alt", "neo-rename--dialog.js", "interfaceOpenRenameDialog20250813"))({ filterInputText: item.imgUrl, inputMode: "single", navigationImgUrls, initialTextValuesByImgUrl, onUpdateCallback: async ({ oldImgUrl, newImgUrl, newTitle, newPathRel, newAltText }) => {
            const renamedItem = mediaItems.find(mediaItem => mediaItem.imgUrl === oldImgUrl || mediaItem.imgUrl.replace(/^https?:/, "") === oldImgUrl.replace(/^https?:/, ""));
            if (!renamedItem) { return; }
            for (const initialImgUrl of Object.keys(initialTextValuesByImgUrl)) { if (initialImgUrl.replace(/^https?:/, "") === oldImgUrl.replace(/^https?:/, "")) { delete initialTextValuesByImgUrl[initialImgUrl]; break; } }
            renamedItem.imgUrl = newImgUrl; renamedItem.thumbnailUrl = newImgUrl; renamedItem.filename = newPathRel.split("/").pop(); renamedItem.modifiedDate = new Date().toISOString().replace("T", " ").substring(0, 19);
            renamedItem.fields.title.value = newTitle; renamedItem.fields.title.initialValue = newTitle; renamedItem.fields.title.savedValue = newTitle; renamedItem.fields.title.origin = fieldOrigins.saved;
            renamedItem.fields.alt.value = newAltText ?? ""; renamedItem.fields.alt.initialValue = newAltText ?? ""; renamedItem.fields.alt.savedValue = newAltText ?? ""; renamedItem.fields.alt.origin = fieldOrigins.saved;
            applyFiltersAndSort();
            await (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: oldImgUrl, textType: "title" }); await (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: oldImgUrl, textType: "alt" });
        } });
    });
    rowNode.querySelector(".neo-alt--copy-button").addEventListener("click", async () => { const copyIconNode = rowNode.querySelector(".neo-alt--copy-button img"); const copyLabelNode = rowNode.querySelector(".neo-alt--copy-label"); await navigator.clipboard.writeText(item.imgUrl); copyIconNode.src = pluginUrl() + "/_global-lucide-icons-thirdparty/check.svg"; copyLabelNode.textContent = neo__("URL copied", "URL kopiert"); setTimeout(() => { copyIconNode.src = pluginUrl() + "/img/_global-button-link-icon.svg"; copyLabelNode.textContent = neo__("Copy URL", "URL kopieren"); }, 1000); });
    const syncRowState = function () {
        const saveStatusNode = rowNode.querySelector(".neo-alt--save-status");
        const rowSaving = item.operation === itemOperations.saving; rowNode.classList.toggle("neo-alt--row-changed", mediaItemDiffersFromInitial(item)); rowNode.classList.toggle("neo-alt--row-saving", rowSaving);
        saveStatusNode.hidden = !rowSaving;
        for (const field of ["title", "alt"]) { const generatedByAi = item.fields[field].origin === fieldOrigins.ai; const differsFromSaved = fieldDiffersFromSaved(item, field); const fieldNode = rowNode.querySelector(`.neo-alt--${field}-field`); const saveTooltipNode = rowNode.querySelector(`.neo-alt--${field}-save-tooltip`); const saveButtonNode = saveTooltipNode?.querySelector(".neo-alt--field-save-button"); const saveLabelNode = saveTooltipNode?.querySelector(".neo-alt--field-save-label"); const saveLabel = field === "title" ? (generatedByAi ? neo__("Accept and save AI title", "AI-Titel übernehmen und speichern") : neo__("Save title", "Titel speichern")) : (generatedByAi ? neo__("Accept and save AI alt text", "AI-Alt-Text übernehmen und speichern") : neo__("Save alt text", "Alt-Text speichern")); fieldNode?.classList.toggle("neo-alt--field-pending", generatedByAi); fieldNode?.classList.toggle("neo-alt--field-draft", differsFromSaved); if (saveTooltipNode) { saveTooltipNode.hidden = !differsFromSaved; saveButtonNode.setAttribute("aria-label", saveLabel); saveLabelNode.textContent = saveLabel; } }
        updateUi();
    };
    const focusNextInput = async function (field) {
        const itemIndex = filteredMediaItems.indexOf(item); const currentRowNode = virtualScroll.getRowNode(itemIndex);
        if (field === "title" && getFileType(item.imgUrl) === "image") { currentRowNode?.querySelector(".neo-alt--alt-input")?.focus(); return; }
        if (itemIndex >= filteredMediaItems.length - 1) { currentRowNode?.querySelector(field === "title" ? ".neo-alt--title-input" : ".neo-alt--alt-input")?.blur(); return; }
        virtualScroll.scrollToRowIndex(itemIndex + 1); await new Promise(requestAnimationFrame); await new Promise(requestAnimationFrame); virtualScroll.getRowNode(itemIndex + 1)?.querySelector(".neo-alt--title-input")?.focus();
    };
    const registerInputEvents = function (inputNode, field) {
        inputNode.addEventListener("input", async event => { item.fields[field].value = event.target.value; item.fields[field].origin = fieldDiffersFromSaved(item, field) ? fieldOrigins.manual : fieldOrigins.saved; (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: item.imgUrl, textType: field }); syncFieldCounters(rowNode, item); syncRowState(); if (field === "alt") { syncAltInputLayout(rowNode, item); } });
        inputNode.addEventListener("keydown", async event => { const saveShortcut = field === "title" ? event.key === "Enter" : event.key === "Enter" && (event.ctrlKey || event.metaKey); if (!saveShortcut) { return; } event.preventDefault(); if (await saveMediaItem(item, [field])) { await focusNextInput(field); } });
    };
    registerInputEvents(rowNode.querySelector(".neo-alt--title-input"), "title"); if (fileType === "image") { registerInputEvents(rowNode.querySelector(".neo-alt--alt-input"), "alt"); }
    rowNode.querySelectorAll("[data-neo-alt--save-field]").forEach(buttonNode => buttonNode.addEventListener("click", async () => { await saveMediaItem(item, [buttonNode.getAttribute("data-neo-alt--save-field")]); }));
    rowNode.querySelectorAll("[data-neo-alt--generate-type]").forEach(buttonNode => { const generateType = buttonNode.getAttribute("data-neo-alt--generate-type"); buttonNode.addEventListener("click", async () => { await generateTextForItem(item, generateType); }); });
    rowNode.querySelector(".neo-alt--row-selection-checkbox").addEventListener("click", async event => {
        const itemId = String(item.id); const anchorIndex = bulkSelectionAnchor ? filteredMediaItems.findIndex(filteredItem => String(filteredItem.id) === bulkSelectionAnchor.itemId) : -1; const itemIndex = filteredMediaItems.indexOf(item);
        if (event.shiftKey && anchorIndex >= 0) { for (const rangeItem of filteredMediaItems.slice(Math.min(anchorIndex, itemIndex), Math.max(anchorIndex, itemIndex) + 1)) { if (bulkSelectionAnchor.checked) { selectedMediaItemIds.add(String(rangeItem.id)); } else { selectedMediaItemIds.delete(String(rangeItem.id)); } } }
        else { if (event.currentTarget.checked) { selectedMediaItemIds.add(itemId); } else { selectedMediaItemIds.delete(itemId); } bulkSelectionAnchor = { itemId, checked: event.currentTarget.checked }; }
        await updateUi(true);
    });
    rowNode.querySelector(".neo-alt--undo-button").addEventListener("click", async () => { for (const field of ["title", "alt"]) { item.fields[field].value = item.fields[field].initialValue; item.fields[field].origin = fieldDiffersFromSaved(item, field) ? fieldOrigins.manual : fieldOrigins.saved; } await updateUi(true); });
    if (item.fields.alt.width) { rowNode.querySelector(".neo-alt--alt-input").style.width = item.fields.alt.width; } if (item.fields.alt.height) { rowNode.querySelector(".neo-alt--alt-input").style.height = item.fields.alt.height; }
    return rowNode;
}, document.scrollingElement);
compactRowsMediaQuery.addEventListener("change", () => virtualScroll.rerenderList());
mobileAltInputMediaQuery.addEventListener("change", () => virtualScroll.rerenderList());

async function updateUi(updateRows = false) {
    if (updateRows) { await virtualScroll.updateRowData(); tableNode.querySelectorAll(".neo-alt--row").forEach(rowNode => { const item = mediaItemsById.get(rowNode.getAttribute("data-neo-alt--media-id")); rowNode.querySelector(".neo-alt--title-input").value = item.fields.title.value; rowNode.querySelector(".neo-alt--row-selection-checkbox").checked = selectedMediaItemIds.has(String(item.id)); if (rowNode.querySelector(".neo-alt--alt-input")) { rowNode.querySelector(".neo-alt--alt-input").value = item.fields.alt.value; syncAltInputLayout(rowNode, item); } }); }
    const draftsExist = mediaItems.some(item => mediaItemHasDraft(item)); const itemOperationRunning = mediaItems.some(item => item.operation !== itemOperations.idle); const pageBusy = pageOperation !== pageOperations.idle; const generationRunning = pageOperation === pageOperations.generating || pageOperation === pageOperations.cancellingGeneration; const controlsLocked = draftsExist || itemOperationRunning || pageBusy;
    const exitIntentRelevantStateExists = draftsExist || generationRunning || mediaItems.some(item => item.operation === itemOperations.generatingTitle || item.operation === itemOperations.generatingAlt); if (exitIntentRelevantStateExists && !exitIntentRelevantStateExisted) { exitIntentNextAllowedTimestamp = 0; } if (!exitIntentRelevantStateExists) { exitIntentNextAllowedTimestamp = 0; } exitIntentRelevantStateExisted = exitIntentRelevantStateExists;
    const showReviewActions = draftsExist && !generationRunning;
    const selectedVisibleItemCount = filteredMediaItems.filter(item => selectedMediaItemIds.has(String(item.id))).length; const generationItemCount = filteredMediaItems.filter(item => selectedMediaItemIds.has(String(item.id)) && getBulkGenerationTypes(item).length > 0).length;
    searchInputNode.disabled = controlsLocked; searchClearNode.disabled = controlsLocked; emptyFilterNode.disabled = controlsLocked;
    searchTooltipNode.toggleAttribute("disabled", !controlsLocked); emptyFilterTooltipNode.toggleAttribute("disabled", !controlsLocked);
    bulkSelectionCheckboxNode.checked = filteredMediaItems.length > 0 && selectedVisibleItemCount === filteredMediaItems.length; bulkSelectionCheckboxNode.indeterminate = selectedVisibleItemCount > 0 && selectedVisibleItemCount < filteredMediaItems.length; bulkSelectionCheckboxNode.disabled = showReviewActions; tableNode.querySelectorAll(".neo-alt--row-selection-checkbox").forEach(checkboxNode => checkboxNode.disabled = showReviewActions);
    bulkGenerateButtonNode.textContent = neo__("Generate selected (%s)", "Ausgewählte generieren (%s)").replace("%s", generationItemCount.toLocaleString()); bulkGenerateButtonNode.toggleAttribute("disabled", pageBusy || generationItemCount === 0); bulkGenerateButtonNode.toggleAttribute("loading", generationRunning);
    bulkProgressNode.hidden = !generationRunning; bulkCancelButtonNode.hidden = !generationRunning; bulkCancelButtonNode.disabled = pageOperation === pageOperations.cancellingGeneration;
    document.querySelector("#neo-alt--bulk-controls").classList.toggle("neo-alt--review-actions-visible", showReviewActions);
    document.querySelectorAll("#neo-alt--bulk-mode button").forEach(buttonNode => buttonNode.disabled = pageBusy);
    acceptAllButtonNode.toggleAttribute("disabled", pageBusy || itemOperationRunning); discardAllButtonNode.toggleAttribute("disabled", pageBusy || itemOperationRunning); acceptAllButtonNode.toggleAttribute("loading", pageOperation === pageOperations.acceptingDrafts); discardAllButtonNode.toggleAttribute("loading", pageOperation === pageOperations.discardingDrafts);
    document.querySelector("#neo-alt--media-count").textContent = filteredMediaItems.length.toLocaleString() + " " + neo__("media items", "Medien");
    document.querySelector("#neo-alt--empty-state").hidden = filteredMediaItems.length > 0; tableContainerNode.hidden = filteredMediaItems.length === 0;
}
async function applyFiltersAndSort() {
    const searchTerms = searchInputNode.value.trim().split(/\s+/); const includedSearchText = searchTerms.filter(searchTerm => !searchTerm.startsWith("-") || searchTerm === "-").join(" "); const excludedSearchTerms = searchTerms.filter(searchTerm => searchTerm.startsWith("-") && searchTerm !== "-").map(searchTerm => searchTerm.substring(1));
    const onlyEmptyAltTexts = emptyFilterNode.value === "empty"; const sortMode = sortSelectNode.value; const sortByAltTextLength = sortMode === "alt-text-length-ascending" || sortMode === "alt-text-length-descending";
    filteredMediaItems = mediaItems.filter(item => { const searchValues = [item.fields.title.value, item.filename]; return matchesSearchText(includedSearchText, searchValues) && excludedSearchTerms.every(searchTerm => !matchesSearchText(searchTerm, searchValues)) && (!onlyEmptyAltTexts || (getFileType(item.imgUrl) === "image" && item.fields.alt.value.trim() === "")) && (!sortByAltTextLength || getFileType(item.imgUrl) === "image"); });
    filteredMediaItems.sort((itemA, itemB) => {
        if (sortMode === "modified-date") { return itemB.modifiedDate.localeCompare(itemA.modifiedDate); }
        if (sortMode === "title") { return itemA.fields.title.value.localeCompare(itemB.fields.title.value, undefined, { sensitivity: "base" }); }
        if (sortMode === "url") { return itemA.imgUrl.localeCompare(itemB.imgUrl, undefined, { sensitivity: "base" }); }
        if (sortByAltTextLength) { const lengthDifference = Array.from(itemA.fields.alt.value).length - Array.from(itemB.fields.alt.value).length; return (sortMode === "alt-text-length-ascending" ? lengthDifference : -lengthDifference) || itemB.uploadDate.localeCompare(itemA.uploadDate); }
        return itemB.uploadDate.localeCompare(itemA.uploadDate);
    });
    await virtualScroll.rerenderList(); await updateUi();
}

async function generateTextForItem(item, textType, bulkRequest = false, bulkConfirmationState = null) {
    if (item.operation !== itemOperations.idle || (pageOperation !== pageOperations.idle && !bulkRequest)) { return false; }
    const currentRowNode = virtualScroll.getRowNode(filteredMediaItems.indexOf(item)); const currentTitle = currentRowNode?.querySelector(".neo-alt--title-input")?.value ?? item.fields.title.value; const currentAltText = currentRowNode?.querySelector(".neo-alt--alt-input")?.value ?? item.fields.alt.value;
    item.queuedGenerationTypes = item.queuedGenerationTypes.filter(queuedType => queuedType !== textType); item.operation = textType === "title" ? itemOperations.generatingTitle : itemOperations.generatingAlt; item.error = "";

    const itemIndex = filteredMediaItems.indexOf(item); const rowHeight = compactRowsMediaQuery.matches ? 144 : 128; const rowScrollTop = tableNode.getBoundingClientRect().top + document.scrollingElement.scrollTop + itemIndex * rowHeight;
    const rowIsNotWithinInnerThird = itemIndex >= 0 && (rowScrollTop + rowHeight <= document.scrollingElement.scrollTop + window.innerHeight / 3 || rowScrollTop >= document.scrollingElement.scrollTop + window.innerHeight * (2 / 3));
    const scrollCooldownReached = Date.now() - lastUserScrollTimestamp > 30 * 1000 && Date.now() - lastAutoScrollTimestamp > 0.25 * 1000;
    if (rowIsNotWithinInnerThird && scrollCooldownReached) { window.scrollTo({ top: rowScrollTop - window.innerHeight / 3 + rowHeight / 2, behavior: "smooth" }); lastAutoScrollTimestamp = Date.now(); }
    const getNearbyTitles = function () {
        const itemIndex = mediaItemsByUploadDate.indexOf(item);
        if (itemIndex < 0) { return []; }
        let newerItems = mediaItemsByUploadDate.slice(Math.max(0, itemIndex - 5), itemIndex); let olderItems = mediaItemsByUploadDate.slice(itemIndex + 1, itemIndex + 6);
        if (newerItems.length < 5) { olderItems = mediaItemsByUploadDate.slice(itemIndex + 1, itemIndex + 1 + 10 - newerItems.length); }
        if (olderItems.length < 5) { newerItems = mediaItemsByUploadDate.slice(Math.max(0, itemIndex - (10 - olderItems.length)), itemIndex); }
        return [...newerItems, ...olderItems].map(nearbyItem => nearbyItem.fields.title.value.trim() || nearbyItem.fields.title.initialValue).filter(title => title !== "" && title !== item.fields.title.value.trim()).slice(0, 10);
    };
    let generatedText = null;
    try { await updateUi(true); generatedText = await (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceGenerateImageText20260713"))({ imageUrl: item.imgUrl, textType, nearbyTitles: textType === "title" ? getNearbyTitles() : [], imageTitle: currentTitle.trim() || item.fields.title.initialValue, imageAltText: currentAltText.trim(), promptAddition: bulkPromptAddition, bulkConfirmationState, errorHandler: bulkRequest ? error => { item.error = error?.message || neo__("Could not generate text.", "Text konnte nicht generiert werden."); if (error?.data?.code === "neo-ai__quota-exhausted") { bulkConfirmationState.cancelled = true; } } : null }); }
    catch (error) { neoError("neoAlt generation failed:", error); if (bulkRequest) { item.error = error?.message || neo__("Could not generate text.", "Text konnte nicht generiert werden."); } else { await Swal.fire({ icon: "error", title: neo__("Generation failed", "Generierung fehlgeschlagen"), text: error?.message || neo__("Could not generate text.", "Text konnte nicht generiert werden.") }); } }
    finally { item.operation = itemOperations.idle; }
    if (generatedText === null) { await updateUi(true); return false; }
    item.fields[textType].value = generatedText; item.fields[textType].origin = fieldDiffersFromSaved(item, textType) ? fieldOrigins.ai : fieldOrigins.saved;
    await updateUi(true); return true;
}

searchInputNode.addEventListener("input", applyFiltersAndSort);
searchClearNode.addEventListener("click", () => { searchInputNode.value = ""; applyFiltersAndSort(); });
emptyFilterNode.addEventListener("change", applyFiltersAndSort);
sortSelectNode.addEventListener("change", applyFiltersAndSort);
document.querySelectorAll("[data-neo-alt--bulk-mode]").forEach(buttonNode => buttonNode.addEventListener("click", async () => { selectedBulkMode = buttonNode.getAttribute("data-neo-alt--bulk-mode"); document.querySelectorAll("[data-neo-alt--bulk-mode]").forEach(modeButtonNode => modeButtonNode.classList.toggle("neo-alt--bulk-mode-selected", modeButtonNode === buttonNode)); await updateUi(true); }));
bulkSelectionCheckboxNode.addEventListener("click", async event => { const selectVisibleItems = event.currentTarget.checked; for (const item of filteredMediaItems) { if (selectVisibleItems) { selectedMediaItemIds.add(String(item.id)); } else { selectedMediaItemIds.delete(String(item.id)); } } bulkSelectionAnchor = null; await updateUi(true); });
bulkGenerateButtonNode.addEventListener("click", async () => {
    const generationItems = filteredMediaItems.filter(item => selectedMediaItemIds.has(String(item.id)) && getBulkGenerationTypes(item).length > 0);
    if (generationItems.length === 0 || pageOperation !== pageOperations.idle) { return; }
    if (await (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceShowUnsupportedFreeProviderEnvironmentDialog20260729"))()) { return; }
    const promptAdditionResult = await Swal.fire({ icon: "question", title: neo__("Additional prompt instruction", "Zusätzliche Prompt-Anweisung"), text: neo__("Optionally add an instruction for this bulk generation.", "Ergänze optional eine Anweisung für diese Bulk-Generierung."), input: "textarea", inputValue: "", inputPlaceholder: neo__("Leave empty for default prompt", "Für Standard-Prompt leer lassen"), showCancelButton: true, confirmButtonText: neo__("Start generation", "Generierung starten"), cancelButtonText: neo__("Cancel", "Abbrechen") });
    if (!promptAdditionResult.isConfirmed) { return; }
    bulkPromptAddition = promptAdditionResult.value || "";
    generationItems.forEach(item => item.queuedGenerationTypes = getBulkGenerationTypes(item)); pageOperation = pageOperations.generating; await updateUi(true);
    const failedItems = []; let generatedBulkDraftCount = 0;
    const bulkConfirmationState = { hidden: false, cancelled: false, quotaErrorDialogShown: false, requestCount: generationItems.reduce((requestCount, item) => requestCount + getBulkGenerationTypes(item).length, 0), confirmationQueue: Promise.resolve(true) };
    const generationQueue = [...generationItems]; let completedGenerationItems = 0; bulkProgressNode.textContent = `0 / ${generationItems.length}`;

    const generateNextQueueItem = async () => {
        if (!(generationQueue.length > 0 && pageOperation === pageOperations.generating)) { return false; }
        const item = generationQueue.shift();
        const textTypes = getBulkGenerationTypes(item); let generationSuccessful = true;
        for (const textType of textTypes) { if (pageOperation !== pageOperations.generating) { generationSuccessful = false; break; } if (!await generateTextForItem(item, textType, true, bulkConfirmationState)) { generationSuccessful = false; if (bulkConfirmationState.cancelled) { pageOperation = pageOperations.cancellingGeneration; } else if (item.error !== "") { failedItems.push(item.filename); } break; } if (fieldDiffersFromSaved(item, textType)) { generatedBulkDraftCount++; } }
        completedGenerationItems++; bulkProgressNode.textContent = `${completedGenerationItems} / ${generationItems.length}`;
        return generationSuccessful;
    };

    let initialGenerationSuccessful = false;
    while (generationQueue.length > 0 && pageOperation === pageOperations.generating && !initialGenerationSuccessful) { initialGenerationSuccessful = await generateNextQueueItem(); }

    const generationWorkers = initialGenerationSuccessful ? Array.from({ length: Math.min(4, generationQueue.length) }, async () => {
        while (generationQueue.length > 0 && pageOperation === pageOperations.generating) { await generateNextQueueItem(); }
    }) : [];
    await Promise.all(generationWorkers);
    const bulkCancelled = pageOperation === pageOperations.cancellingGeneration; generationItems.forEach(item => item.queuedGenerationTypes = []); if (generatedBulkDraftCount === 0) { bulkPromptAddition = ""; } pageOperation = pageOperations.idle; await updateUi(true);
    if (bulkCancelled) { return; }
    if (failedItems.length > 0) { neoError("neoAlt bulk generation failed for " + failedItems.length + " media items."); await Swal.fire({ icon: "error", title: neo__("Some generations failed", "Einige Generierungen sind fehlgeschlagen"), text: failedItems.slice(0, 10).join(", ") + (failedItems.length > 10 ? ` (+${failedItems.length - 10})` : "") }); return; }
    await Swal.fire({ icon: "success", title: neo__("Generation completed", "Generierung abgeschlossen"), text: neo__("Review and accept or discard the generated suggestions.", "Prüfe die generierten Vorschläge und nimm sie an oder verwirf sie.") });
});
bulkCancelButtonNode.addEventListener("click", async () => {
    pageOperation = pageOperations.cancellingGeneration; await updateUi();
    await Swal.fire({ icon: "info", title: neo__("Generation cancelled", "Generierung abgebrochen"), text: neo__("Generated suggestions remain available for review.", "Bereits generierte Vorschläge bleiben zur Prüfung erhalten.") });
});
acceptAllButtonNode.addEventListener("click", async () => {
    const changedItems = mediaItems.filter(item => mediaItemHasDraft(item));
    for (const item of changedItems) { if (fieldDiffersFromSaved(item, "title") && item.fields.title.value.trim() === "") { item.fields.title.value = "Untitled"; item.fields.title.origin = fieldOrigins.manual; } }
    pageOperation = pageOperations.acceptingDrafts; await updateUi(true);
    try {
        const items = changedItems.map(item => { const fields = [fieldDiffersFromSaved(item, "title") ? "title" : null, fieldDiffersFromSaved(item, "alt") ? "alt" : null].filter(Boolean); const requestItem = { "media-id": item.id, fields }; if (fields.includes("title")) { requestItem.title = item.fields.title.value; } if (fields.includes("alt")) { requestItem["alt-text"] = item.fields.alt.value; } return requestItem; });
        const response = await fetchEndpoint("/wp-json/neo/alt-save", { method: "POST", body: { items } }).then(extractJson);
        for (const savedItem of response.items) { const item = changedItems.find(item => item.id === savedItem.mediaId); const savedFields = items.find(requestItem => requestItem["media-id"] === savedItem.mediaId).fields; if (savedFields.includes("title")) { item.fields.title.value = savedItem.title; item.fields.title.savedValue = savedItem.title; item.fields.title.origin = fieldOrigins.saved; (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: item.imgUrl, textType: "title" }); } if (savedFields.includes("alt")) { item.fields.alt.value = savedItem.altText; item.fields.alt.savedValue = savedItem.altText; item.fields.alt.origin = fieldOrigins.saved; (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: item.imgUrl, textType: "alt" }); } }
        const savedMediaIds = new Set(response.items.map(savedItem => savedItem.mediaId)); const noChangesSavedSuffix = neo__(" (No changes saved)", " (Keine Änderungen gespeichert)");
        for (const item of mediaItems.filter(item => item.error !== "" && !savedMediaIds.has(item.id) && !item.error.endsWith(noChangesSavedSuffix))) { item.error += noChangesSavedSuffix; }
        const savedChangeCount = items.reduce((count, item) => count + item.fields.length, 0);
        bulkPromptAddition = "";
        if (emptyFilterNode.value === "empty") { await applyFiltersAndSort(); }
        await Swal.fire({ icon: "success", title: neo__("Saved changes: %s", "Gespeicherte Änderungen: %s").replace("%s", savedChangeCount.toLocaleString()) });
    } catch (error) { neoError("neoAlt bulk save failed:", error); await Swal.fire({ icon: "error", title: neo__("Could not save changes", "Änderungen konnten nicht gespeichert werden"), text: error?.message || neo__("Could not save changes.", "Änderungen konnten nicht gespeichert werden.") }); }
    finally { pageOperation = pageOperations.idle; await updateUi(true); }
});
discardAllButtonNode.addEventListener("click", async () => {
    const result = await Swal.fire({ icon: "warning", title: neo__("Discard all unsaved changes?", "Alle ungespeicherten Änderungen verwerfen?"), text: neo__("All manual changes and AI suggestions will be replaced with their last saved values.", "Alle manuellen Änderungen und AI-Vorschläge werden durch ihre zuletzt gespeicherten Werte ersetzt."), showCancelButton: true, confirmButtonText: neo__("Discard all", "Alle verwerfen"), cancelButtonText: neo__("Cancel", "Abbrechen") });
    if (!result.isConfirmed) { return; }
    pageOperation = pageOperations.discardingDrafts; await updateUi(true);
    try { for (const item of mediaItems.filter(item => mediaItemHasDraft(item))) { for (const field of ["title", "alt"]) { if (!fieldDiffersFromSaved(item, field)) { continue; } item.fields[field].value = item.fields[field].savedValue; item.fields[field].origin = fieldOrigins.saved; (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl: item.imgUrl, textType: field }); } } bulkPromptAddition = ""; }
    finally { pageOperation = pageOperations.idle; await updateUi(true); }
});

async function showExitIntentWarning() {
    const generationRunning = pageOperation === pageOperations.generating || pageOperation === pageOperations.cancellingGeneration || mediaItems.some(item => item.operation === itemOperations.generatingTitle || item.operation === itemOperations.generatingAlt); const unsavedChangesExist = mediaItems.some(item => mediaItemHasDraft(item));
    if (!(unsavedChangesExist || generationRunning) || exitIntentDialogOpen || Date.now() < exitIntentNextAllowedTimestamp || Swal.isVisible()) { return; }
    exitIntentDialogOpen = true; exitIntentClosedForHiddenTab = false; exitIntentNextAllowedTimestamp = Infinity;
    await Swal.fire({ icon: "warning", title: neo__("Unsaved changes", "Ungespeicherte Änderungen"), text: generationRunning ? neo__("It looks like you want to close this tab while a generation is still running. Save all changes after it finishes so that nothing is lost.", "Du scheinst diesen Tab schließen zu wollen, während noch eine Generierung läuft. Speichere danach alle Änderungen, damit nichts verloren geht.") : neo__("It looks like you want to close this tab, but you still have unsaved changes. Save them now so that nothing is lost.", "Du scheinst diesen Tab schließen zu wollen, hast aber noch ungespeicherte Änderungen. Speichere sie jetzt, damit nichts verloren geht."), confirmButtonText: neo__("OK", "OK"), allowOutsideClick: false, allowEscapeKey: false });
    exitIntentDialogOpen = false;
    if (exitIntentClosedForHiddenTab) { exitIntentClosedForHiddenTab = false; exitIntentNextAllowedTimestamp = 0; return; }
    exitIntentNextAllowedTimestamp = Date.now() + 5 * 60 * 1000;
}
document.addEventListener("mouseout", event => {
    const pointerMovementX = lastPointerPosition && previousPointerPosition ? lastPointerPosition.x - previousPointerPosition.x : 0; const pointerMovementY = lastPointerPosition && previousPointerPosition ? lastPointerPosition.y - previousPointerPosition.y : 0;
    const leftThroughTopEdge = event.relatedTarget === null && event.clientY <= 0 && event.clientX > 0 && event.clientX < window.innerWidth;
    const movedUpRecently = Date.now() - lastPointerMoveTimestamp <= 250 && lastPointerPosition?.y <= 10 && pointerMovementY < 0 && Math.abs(pointerMovementY) >= Math.abs(pointerMovementX);
    const scrollCooldownReached = Date.now() - lastUserScrollTimestamp > 1000;
    if (!(leftThroughTopEdge && movedUpRecently && scrollCooldownReached)) { return; }
    showExitIntentWarning();
});
document.addEventListener("visibilitychange", () => { if (!(document.visibilityState === "hidden" && exitIntentDialogOpen)) { return; } exitIntentClosedForHiddenTab = true; Swal.close(); });
window.addEventListener("beforeunload", event => { if (!(mediaItems.some(item => mediaItemHasDraft(item) || item.operation !== itemOperations.idle) || pageOperation !== pageOperations.idle)) { return; } showExitIntentWarning(); event.preventDefault(); event.returnValue = ""; });

applyFiltersAndSort();
