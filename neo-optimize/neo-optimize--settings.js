import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { formatKb } from "./_global--math.js";

import { observeClick, observeOnce } from "./_global--observer.js";
import { neo__, toFixed } from "./_global--translation.js";
import Swal from "./_global-sweetalert2.js";

observeOnce("#neo-optimize--default-settings-root", async (root) => {
    const state = { outputFormat: root.getAttribute("data-selected-output-format"), quality: Number(root.getAttribute("data-selected-quality")), variants: {}, originalSizeBytes: null, previewCacheCleared: false, previewDataLoadStarted: false, previewLoadPromise: null, queuedPreviewSelection: null, allPreviewImagesLoaded: false };
    state.retinaFactor = Number(root.getAttribute("data-selected-retina-factor")); 
    const previewImg     = await observeOnce("[data-preview-img]",     undefined, { domRoot: root });
    const previewOverlay = await observeOnce("[data-preview-overlay]", undefined, { domRoot: root });
    const setPreviewLoading = (isLoading) => { root.setAttribute("data-preview-loading", String(isLoading)); };
    let variantKey = (outputFormat, quality) => `${outputFormat}-${quality}`; variantKey = (outputFormat, quality) => `${outputFormat}-${quality}-${state.retinaFactor}`; 
    const selectedVariant = () => state.variants[variantKey(state.outputFormat, state.quality)] ?? null;
    const currentPreviewSelection = (changedAxis = null, preloadAll = false) => { const selection = { outputFormat: state.outputFormat, quality: state.quality, changedAxis, preloadAll }; selection.retinaFactor = state.retinaFactor;return selection; };
    const formatSavingPercent = (sizeBytes) => typeof sizeBytes === "number" && typeof state.originalSizeBytes === "number" && state.originalSizeBytes > 0 ? ` ${Math.round((sizeBytes / state.originalSizeBytes - 1) * 100)}%` : "";
    const setChoiceSize = (sizeNode, sizeBytes) => { sizeNode.textContent = formatKb(sizeBytes); if (formatSavingPercent(sizeBytes) === "") { return; } const savingNode = document.createElement("span"); savingNode.className = "neo-optimize--choice-saving"; savingNode.textContent = formatSavingPercent(sizeBytes); sizeNode.append(savingNode); };
    const renderUi = () => {
        root.setAttribute("data-selected-output-format", state.outputFormat);
        root.setAttribute("data-selected-quality", String(state.quality));
        root.querySelectorAll("[data-output-format]").forEach((button) => { const outputFormat = button.getAttribute("data-output-format"); button.setAttribute("aria-pressed", String(outputFormat === state.outputFormat)); if (!button.disabled) { setChoiceSize(button.querySelector("[data-size-for-output-format]"), state.variants[variantKey(outputFormat, state.quality)]?.size_bytes); } });
        root.querySelectorAll("[data-quality]").forEach((button) => { button.setAttribute("aria-pressed", String(Number(button.getAttribute("data-quality")) === state.quality)); setChoiceSize(button.querySelector("[data-size-for-quality]"), state.variants[variantKey(state.outputFormat, Number(button.getAttribute("data-quality")))]?.size_bytes); });
        root.setAttribute("data-selected-retina-factor", String(state.retinaFactor)); root.querySelectorAll("[data-retina-factor]").forEach((button) => { button.setAttribute("aria-pressed", String(Number(button.getAttribute("data-retina-factor")) === state.retinaFactor)); setChoiceSize(button.querySelector("[data-size-for-retina-factor]"), state.variants[`${state.outputFormat}-${state.quality}-${Number(button.getAttribute("data-retina-factor"))}`]?.size_bytes); }); 
        if (state.previewCacheCleared) { previewOverlay.textContent = neo__("Regenerating preview images...", "Vorschaubilder werden neu generiert..."); return; }
        const variant = selectedVariant(); if (!variant) { previewOverlay.textContent = neo__("Loading preview images...", "Vorschaubilder laden..."); return; }
        previewImg.src = variant.url;
        previewOverlay.textContent = neo__(`${variant.quality}% quality, Format: ${variant.format}, Engine: ${variant.engine}, ${formatKb(variant.size_bytes)} vs. ${formatKb(state.originalSizeBytes)} original`, `Komprimierte Vorschau: ${variant.quality}% Qualität, Format: ${variant.format}, Engine: ${variant.engine}, ${formatKb(variant.size_bytes)} vs. ${formatKb(state.originalSizeBytes)} Original`);
        previewOverlay.textContent = neo__(`${variant.quality}% quality, ${toFixed(variant.retina_factor / 100, 2)}x retina, Format: ${variant.format}, Engine: ${variant.engine}, ${formatKb(variant.size_bytes)} vs. ${formatKb(state.originalSizeBytes)} original`, `Komprimierte Vorschau: ${variant.quality}% Qualität, ${toFixed(variant.retina_factor / 100, 2)}x Retina, Format: ${variant.format}, Engine: ${variant.engine}, ${formatKb(variant.size_bytes)} vs. ${formatKb(state.originalSizeBytes)} Original`); 
    };
    const loadPreviewData = (changedAxis = null, preloadAll = false) => {
        if (preloadAll) { changedAxis = null; }
        if (state.queuedPreviewSelection && state.queuedPreviewSelection.changedAxis !== changedAxis) { changedAxis = null; }
        state.queuedPreviewSelection = currentPreviewSelection(changedAxis, preloadAll || Boolean(state.queuedPreviewSelection?.preloadAll));
        root.setAttribute("data-preview-background-loading", "true");
        if (state.previewLoadPromise) { return state.previewLoadPromise; }
        state.previewLoadPromise = (async () => {
            while (state.queuedPreviewSelection) {
                const previewSelection = state.queuedPreviewSelection; state.queuedPreviewSelection = null;
                const requestBody = { "output-format": previewSelection.outputFormat, quality: previewSelection.quality };
                requestBody["retina-factor"] = previewSelection.retinaFactor; 
                if (previewSelection.changedAxis) { requestBody["changed-axis"] = previewSelection.changedAxis; }
                if (previewSelection.preloadAll) { requestBody["preload-all"] = true; }
                try {
                    const previewData = await fetchEndpoint("/wp-json/neo/optimize-preview-settings", { method: "POST", body: requestBody }).then(extractJson);
                    state.variants = { ...state.variants, ...(previewData.variants ?? {}) }; state.originalSizeBytes = previewData.original_size_bytes; state.previewCacheCleared = false; if (previewSelection.preloadAll) { state.allPreviewImagesLoaded = true; }
                    setPreviewLoading(!selectedVariant()); renderUi();
                } catch (error) {
                    if (!selectedVariant() && state.queuedPreviewSelection) { setPreviewLoading(true); renderUi(); }
                    if (!selectedVariant() && !state.queuedPreviewSelection) { setPreviewLoading(false); previewOverlay.textContent = error.message || neo__("Could not load preview.", "Vorschau konnte nicht geladen werden."); }
                    if (!state.queuedPreviewSelection) { throw error; }
                }
            }
        })().finally(() => { state.previewLoadPromise = null; root.setAttribute("data-preview-background-loading", "false"); });
        return state.previewLoadPromise;
    };
    const loadPreviewDataOnce = async () => {
        if (state.previewDataLoadStarted) { return; }
        state.previewDataLoadStarted = true;
        try { await loadPreviewData(); }
        catch (error) { state.previewDataLoadStarted = false; }
    };
    const loadPreviewDataWhenSectionVisible = async () => {
        while (root.isConnected && root.getClientRects().length === 0) { await new Promise(requestAnimationFrame); }
        if (!root.isConnected) { return; }
        loadPreviewDataOnce();
    };
    document.addEventListener("neoOptimizePreloadAllPreviewImages", (event) => { loadPreviewData(null, true).then(() => event.detail.resolve()).catch((error) => event.detail.reject(error)); });
    document.addEventListener("neoOptimizePreviewCacheCleared", () => { state.variants = {}; state.allPreviewImagesLoaded = false; state.previewCacheCleared = true; setPreviewLoading(true); renderUi(); loadPreviewData().catch(() => {}); });
    let saveSettingsPromise = Promise.resolve();
    const saveSettings = async (button) => {
        const requestBody = { "output-format": state.outputFormat, quality: state.quality };
        requestBody["retina-factor"] = state.retinaFactor; 
        saveSettingsPromise = saveSettingsPromise.then(async () => {
            button.querySelector(".neo-optimize--choice-size").textContent = neo__("Saving...", "Speichern...");
            try { await fetchEndpoint("/wp-json/neo/optimize-default-settings", { method: "POST", body: requestBody }); }
            catch (error) { previewOverlay.textContent = error.message || neo__("Could not save settings.", "Einstellungen konnten nicht gespeichert werden."); }
            finally { renderUi(); }
        });
        await saveSettingsPromise;
    };
    root.addEventListener("click", async (event) => {
        const button = event.target.closest(".neo-optimize--choice-button");
        if (!button || button.disabled) { return; }
        let changedAxis = null;
        if (button.getAttribute("data-output-format")) { const requestedOutputFormat = button.getAttribute("data-output-format"); const fallbackOutputFormat = requestedOutputFormat === "avif" ? "webp" : "avif"; const effectiveOutputFormat = button.getAttribute("data-output-format-supported") === "true" ? requestedOutputFormat : (root.querySelector(`[data-output-format="${fallbackOutputFormat}"]`)?.getAttribute("data-output-format-supported") === "true" ? fallbackOutputFormat : requestedOutputFormat); if (effectiveOutputFormat !== state.outputFormat) { changedAxis = "output-format"; state.outputFormat = effectiveOutputFormat; root.querySelector(".neo-optimize--avif-cache-hint").style.display = "block"; } }
        if (button.getAttribute("data-quality") && Number(button.getAttribute("data-quality")) !== state.quality) { changedAxis = "quality"; state.quality = Number(button.getAttribute("data-quality")); }
        if (button.getAttribute("data-retina-factor") && Number(button.getAttribute("data-retina-factor")) !== state.retinaFactor) { changedAxis = "retina-factor"; state.retinaFactor = Number(button.getAttribute("data-retina-factor")); } 
        if (!changedAxis) { return; }
        setPreviewLoading(!selectedVariant()); renderUi();
        if (!state.allPreviewImagesLoaded) { loadPreviewData(changedAxis).catch(() => {}); }
        if (!window.neoOptimizePreviewOnlyMode) { saveSettings(button); }
    });
    if (jsVar("neoOptimizeIsPlayground")) { loadPreviewDataWhenSectionVisible(); } else { await loadPreviewDataOnce(); }
});
document.addEventListener("neoOptimizePreviewCacheCleared", () => { document.querySelectorAll(".neo-optimize--avif-cache-hint").forEach((hintNode) => { hintNode.style.display = "none"; }); });

observeClick(".neo-optimize--clear-cache-button", async (buttonNode) => {
    if (buttonNode.hasAttribute("loading")) { return; }
    buttonNode.setAttribute("loading", "");
    try {
        await fetchEndpoint("/wp-json/neo/optimize-clear-cache", { method: "POST" });
        document.dispatchEvent(new CustomEvent("neoOptimizePreviewCacheCleared"));
        await Swal.fire({ icon: "success", title: neo__("Cache cleared", "Cache geleert"), text: neo__("The neoOptimize cache has been cleared.", "Der neoOptimize Cache wurde geleert.") });
    } catch (error) {
        await Swal.fire({ icon: "error", title: neo__("Error", "Fehler"), text: error.message || neo__("The neoOptimize cache could not be cleared.", "Der neoOptimize Cache konnte nicht geleert werden.") });
    } finally {
        buttonNode.removeAttribute("loading");
    }
});
