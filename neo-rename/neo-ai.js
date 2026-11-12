import { fetchEndpoint } from "./_global--endpoint.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { extractJson } from "./_global--extract-json.js";
import { domLoaded, observeAttributes, observeClick, observeOnce } from "./_global--observer.js";
import { reloadPage } from "./_global-reload-page.js";
import { neo__ } from "./_global--translation.js";
import { addCacheBust } from "./_global--url-helper.js";
import { getWebsiteHostType } from "./_global--website-host-type.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { showUnsupportedFreeProviderEnvironmentDialog } from "./neo-ai--image-text-generation.js";
import Swal from "./_global-sweetalert2.js";

const connectionDraftsByProvider = {};
let neoAiConnectionProvider = jsVar("neoAiConnectionProvider");
export function getNeoAiConnectionProvider() { return neoAiConnectionProvider; }
observeOnce("#neo-ai--settings", async (root) => {
    await customElements.whenDefined("neo-select-neo-rename");
    await domLoaded();
    const controlsNode               = root.querySelector(".neo-ai--settings-controls");
    const providerSelectNode         = root.querySelector("#neo-ai--provider-select");
    const apiKeyInputNode            = root.querySelector("#neo-ai--api-key-input");
    const apiUrlInputNode            = root.querySelector("#neo-ai--api-url-input");
    const modelInputNode             = root.querySelector("#neo-ai--model-input");
    const apiKeyFieldNode            = root.querySelector(".neo-ai--api-key-field");
    const apiUrlFieldNode            = root.querySelector(".neo-ai--api-url-field");
    const modelFieldNode             = root.querySelector(".neo-ai--model-field");
    const modelCustomizeControlNode  = root.querySelector("#neo-ai--model-customize-control");
    const modelCustomizeCheckboxNode = root.querySelector("#neo-ai--model-customize-checkbox");
    const testButtonNode             = root.querySelector("#neo-ai--test-button");
    const statusNode = root.querySelector("#neo-ai--status");
    const freeProviderStatusNode = root.querySelector("#neo-ai--free-provider-status");
    const freeProviderStatusLoadNode  = root.querySelector("#neo-ai--free-provider-status-load");
    const freeProviderQuotaTextNode   = root.querySelector("#neo-ai--free-provider-quota-text");
    const freeProviderQuotaUpsellNode = root.querySelector("#neo-ai--free-provider-quota-get-more");
    const guideLinkNode = root.querySelector("#neo-ai--api-key-guide-link");
    const guideLinkTextNode = guideLinkNode?.querySelector("span");
    const firstProviderOptionNode = providerSelectNode.querySelector("option:first-child");
    const providers = JSON.parse(controlsNode.getAttribute("data-providers") || "{}");
    const initialConnection = JSON.parse(controlsNode.getAttribute("data-connection") || "{}");
    const readDraft = () => ({ provider: providerSelectNode.value, apiKey: apiKeyInputNode.value, apiUrl: apiUrlInputNode.value, model: modelInputNode.value, customModelEnabled: !modelFieldNode.hidden });
    const writeDraft = (provider, draft = {}) => { providerSelectNode.value = provider; apiKeyInputNode.value = draft.apiKey || ""; apiUrlInputNode.value = draft.apiUrl || ""; modelInputNode.value = draft.model || ""; modelFieldNode.hidden = !(provider === "custom" || draft.customModelEnabled); modelCustomizeCheckboxNode.checked = !modelFieldNode.hidden && provider !== "custom"; };
    const syncProviderPlaceholder = () => { if (!firstProviderOptionNode) { return; } firstProviderOptionNode.textContent = providerSelectNode.value === "" ? neo__("No provider selected", "Kein Anbieter ausgewählt") : neo__("Remove AI connection", "AI-Verbindung entfernen"); providerSelectNode._syncOptions?.(); };
    const syncUi = () => { const provider = providerSelectNode.value; const providerData = providers[provider] || {}; apiKeyFieldNode.hidden = provider === "" || provider === "neoai" || provider === "wordpress"; apiUrlFieldNode.hidden = provider !== "custom"; modelCustomizeControlNode.hidden = provider === "" || provider === "neoai" || provider === "wordpress" || provider === "custom"; if (provider === "custom") { modelFieldNode.hidden = false; } else if (provider === "" || provider === "neoai" || provider === "wordpress") { modelFieldNode.hidden = true; } modelCustomizeCheckboxNode.checked = !modelFieldNode.hidden && provider !== "custom"; controlsNode.classList.toggle("neo-ai--settings-controls-stacked", provider === "custom"); controlsNode.classList.toggle("neo-ai--settings-controls-custom-model", provider !== "custom" && !modelFieldNode.hidden); modelInputNode.setAttribute("placeholder", provider === "custom" ? "llava:latest" : (providerData.default_model || "")); if (providerData.guide_url && guideLinkNode && guideLinkTextNode) { guideLinkNode.href = providerData.guide_url; guideLinkTextNode.textContent = providerData.guide_label || neo__("Guide: API Key", "Anleitung: API-Key"); guideLinkNode.hidden = false; } else if (guideLinkNode) { guideLinkNode.href = "#"; guideLinkNode.hidden = true; } if (freeProviderStatusNode) { freeProviderStatusNode.hidden = provider !== "neoai"; } statusNode.textContent = provider === "" ? neo__("Choose an AI provider to enable neoAI.", "Wähle einen AI-Anbieter, um neoAI zu aktivieren.") : (provider === "neoai" ? neo__("No API key is required.", "Kein API-Key erforderlich.") : (provider === "wordpress" ? neo__("WordPress Integration selected. Save & test checks the configured connection.", "WordPress Integration ausgewählt. Speichern & Testen prüft die konfigurierte Verbindung.") : neo__("Save & test to check the connection.", "Speichern & Testen prüft die Verbindung."))); };
    let currentProvider = initialConnection.provider || "";
    connectionDraftsByProvider[currentProvider] = { provider: currentProvider, apiKey: initialConnection.apiKey || "", apiUrl: initialConnection.apiUrl || "", model: initialConnection.model || "", customModelEnabled: initialConnection.customModelEnabled || currentProvider === "custom" };
    writeDraft(currentProvider, connectionDraftsByProvider[currentProvider]); syncProviderPlaceholder(); syncUi();
    [apiKeyInputNode, apiUrlInputNode, modelInputNode].forEach((inputNode) => inputNode.addEventListener("input", () => { inputNode.value = inputNode.value.trim(); }));
    [apiKeyInputNode, apiUrlInputNode, modelInputNode].forEach((inputNode) => inputNode.addEventListener("keydown", (event) => { if (event.key !== "Enter") { return; } event.preventDefault(); testButtonNode?.click(); }));
    providerSelectNode.addEventListener("change", () => { connectionDraftsByProvider[currentProvider] = readDraft(); currentProvider = providerSelectNode.value; writeDraft(currentProvider, connectionDraftsByProvider[currentProvider] || { provider: currentProvider, customModelEnabled: currentProvider === "custom" }); syncProviderPlaceholder(); syncUi(); });
    modelCustomizeCheckboxNode.addEventListener("change", async () => { modelFieldNode.hidden = !modelCustomizeCheckboxNode.checked; if (modelFieldNode.hidden) { modelInputNode.value = ""; } controlsNode.classList.toggle("neo-ai--settings-controls-stacked", providerSelectNode.value === "custom"); controlsNode.classList.toggle("neo-ai--settings-controls-custom-model", providerSelectNode.value !== "custom" && !modelFieldNode.hidden); connectionDraftsByProvider[providerSelectNode.value] = readDraft(); });

    if (!freeProviderStatusNode || !freeProviderStatusLoadNode || !freeProviderQuotaTextNode) { return; }
    if (getWebsiteHostType() !== "domain") { freeProviderStatusNode.setAttribute("data-loaded", "1"); freeProviderStatusLoadNode.hidden = true; freeProviderQuotaTextNode.hidden = false; freeProviderQuotaTextNode.textContent = `${neo__("neoAI quota", "neoAI Kontingent")}: 0 ${neo__("requests remaining.", "Anfragen verbleibend.")}`; return; }
    const loadFreeProviderQuota = async () => {
        if (freeProviderStatusNode.getAttribute("data-loading") === "1" || freeProviderStatusNode.getAttribute("data-loaded") === "1") { return; }
        freeProviderStatusNode.setAttribute("data-loading", "1"); freeProviderStatusLoadNode.hidden = true; freeProviderQuotaTextNode.hidden = false; freeProviderQuotaTextNode.textContent = neo__("Loading neoAI quota...", "neoAI Kontingent wird geladen..."); if (freeProviderQuotaUpsellNode) { freeProviderQuotaUpsellNode.hidden = true; }
        try { const quota = await fetchEndpoint("/wp-json/neo/ai-free-provider-status", { method: "GET" }).then(extractJson); freeProviderQuotaTextNode.textContent = `${neo__("neoAI quota", "neoAI Kontingent")}: ${quota.remaining_requests} ${neo__("requests remaining.", "Anfragen verbleibend.")}`; if (freeProviderQuotaUpsellNode) { freeProviderQuotaUpsellNode.hidden = false; } }
        catch (error) { freeProviderQuotaTextNode.textContent = error?.message || neo__("neoAI quota is currently unavailable.", "neoAI Kontingent ist aktuell nicht verfügbar."); }
        freeProviderStatusNode.setAttribute("data-loaded", "1"); freeProviderStatusNode.removeAttribute("data-loading");
    };
    observeAttributes(freeProviderStatusNode, async () => {
        if (freeProviderStatusNode.hidden || localStorage.getItem("neoAiFreeProviderConfirmationHidden") !== "1") { return; }
        await loadFreeProviderQuota();
    });
    observeClick(freeProviderStatusLoadNode, async (linkNode, event) => {
        event.preventDefault(); await loadFreeProviderQuota();
    });
});

observeClick("#neo-ai--test-button", async (buttonNode) => {
    const root = buttonNode.closest("#neo-ai--settings");
    const providerSelectNode = root?.querySelector("#neo-ai--provider-select");
    const apiKeyInputNode    = root?.querySelector("#neo-ai--api-key-input");
    const apiUrlInputNode    = root?.querySelector("#neo-ai--api-url-input");
    const modelInputNode     = root?.querySelector("#neo-ai--model-input");
    const statusNode = root?.querySelector("#neo-ai--status");
    if (!providerSelectNode || !apiKeyInputNode || !apiUrlInputNode || !modelInputNode || !statusNode) { return; }
    const provider = providerSelectNode.value;

    if (provider === "neoai" && await showUnsupportedFreeProviderEnvironmentDialog()) { return; }
    const model = !modelInputNode.closest(".neo-ai--model-field")?.hidden ? modelInputNode.value : "";
    buttonNode.setAttribute("loading", "");
    statusNode.textContent = neo__("Saving and testing connection...", "Verbindung wird gespeichert und getestet...");
    neoAiConnectionProvider = provider;
    try {
        const response = await fetchEndpoint("/wp-json/neo/ai-connection-save-and-test", { method: "POST", body: { provider, "api-key": apiKeyInputNode.value, "api-url": apiUrlInputNode.value, model } }).then(extractJson);
        statusNode.setAttribute("data-has-connection", provider !== "" ? "1" : "0");
        if (response.removed) { statusNode.textContent = neo__("AI connection removed.", "AI-Verbindung entfernt."); await Swal.fire({ icon: "success", title: neo__("AI connection removed", "AI-Verbindung entfernt"), text: neo__("neoAI no longer uses a saved AI provider.", "neoAI nutzt keinen gespeicherten AI-Anbieter mehr.") }); return; }
        if (response.ok === false) { statusNode.textContent = neo__("Connection saved, test failed.", "Verbindung gespeichert, Test fehlgeschlagen."); await Swal.fire({ icon: "warning", title: neo__("Connection saved", "Verbindung gespeichert"), text: response.warning || neo__("The connection was saved, but the test failed.", "Die Verbindung wurde gespeichert, aber der Test ist fehlgeschlagen.") }); return; }
        statusNode.textContent = neo__("Connection works.", "Verbindung funktioniert.");
        await Swal.fire({ icon: "success", title: neo__("Connection works", "Verbindung funktioniert"), text: neo__("neoAI can use the saved AI provider.", "neoAI kann den gespeicherten AI-Anbieter nutzen.") });
    } catch (error) {
        statusNode.textContent = neo__("Connection could not be saved.", "Verbindung konnte nicht gespeichert werden.");
        await Swal.fire({ icon: "error", title: neo__("Error", "Fehler"), text: error.message || neo__("Could not save the AI connection.", "AI-Verbindung konnte nicht gespeichert werden.") });
    } finally {
        buttonNode.removeAttribute("loading");
    }
});

observeClick("#neo-ai--usage-reset-button", async (buttonNode) => {
    const confirmation = await Swal.fire({ icon: "warning", title: neo__("Reset usage?", "Verbrauch zurücksetzen?"), text: neo__("All logged neoAI token usage entries will be deleted.", "Alle protokollierten neoAI-Token-Verbrauchseinträge werden gelöscht."), showCancelButton: true, confirmButtonText: neo__("Reset", "Zurücksetzen"), cancelButtonText: neo__("Cancel", "Abbrechen") });
    if (!confirmation.isConfirmed) { return; }
    buttonNode.setAttribute("loading", "");
    try {
        await fetchEndpoint("/wp-json/neo/ai-token-usage-reset", { method: "POST" }).then(extractJson);
        await Swal.fire({ icon: "success", title: neo__("Usage reset", "Verbrauch zurückgesetzt"), text: neo__("The token usage list is empty now. The page will reload.", "Die Token-Verbrauchsliste ist jetzt leer. Die Seite wird neu geladen.") });
        reloadPage();
    } catch (error) {
        await Swal.fire({ icon: "error", title: neo__("Error", "Fehler"), text: error.message || neo__("Could not reset the token usage list.", "Token-Verbrauchsliste konnte nicht zurückgesetzt werden.") });
    } finally {
        buttonNode.removeAttribute("loading");
    }
});

observeOnce("#neo-ai--last-prompt-settings", (root) => {
    const toggleButtonNode = root.querySelector("#neo-ai--last-prompt-toggle-button");
    const promptFieldNode = root.querySelector("#neo-ai--last-prompt-field");
    const promptNode = root.querySelector("#neo-ai--last-prompt-content");
    const refreshButtonNode = root.querySelector("#neo-ai--last-prompt-refresh-button");
    if (!toggleButtonNode || !promptFieldNode || !promptNode || !refreshButtonNode) { return; }
    const loadLastPrompt = async () => {
        const cacheUrl = promptFieldNode.getAttribute("data-cache-url");
        if (!cacheUrl || refreshButtonNode.disabled) { return; }
        refreshButtonNode.disabled = true; refreshButtonNode.setAttribute("data-loading", ""); promptNode.textContent = neo__("Loading last prompt...", "Letzter Prompt wird geladen...");
        try { const response = await fetch(addCacheBust(cacheUrl), { cache: "no-store" }); if (response.status === 404) { promptNode.textContent = neo__("No prompt stored yet.", "Noch kein Prompt gespeichert."); return; } if (!response.ok) { throw new Error(`Could not load last prompt (${response.status}).`); } promptNode.textContent = await response.text() || neo__("No prompt stored yet.", "Noch kein Prompt gespeichert."); }
        catch (error) { promptNode.textContent = error.message || neo__("Could not load the last prompt.", "Der letzte Prompt konnte nicht geladen werden."); }
        finally { refreshButtonNode.disabled = false; refreshButtonNode.removeAttribute("data-loading"); }
    };
    toggleButtonNode.addEventListener("click", async () => {
        if (promptFieldNode.hidden) { promptFieldNode.hidden = false; toggleButtonNode.textContent = neo__("Hide prompt", "Prompt ausblenden"); await loadLastPrompt(); return; }
        promptFieldNode.hidden = true; toggleButtonNode.textContent = neo__("Show prompt", "Prompt anzeigen");
    });
    refreshButtonNode.addEventListener("click", loadLastPrompt);
});

observeOnce("#neo-ai--generated-text-language-dropdown", async (dropdownNode) => {
    dropdownNode.addEventListener("change", async () => {
        const buttonTextBefore = dropdownNode.buttonText;
        dropdownNode.disabled = true; dropdownNode.removeAttribute("success"); dropdownNode.buttonText = "...";
        try {
            await fetchEndpoint("/wp-json/neo/ai-generated-text-language-save", { method: "POST", body: { language: dropdownNode.value } }).then(extractJson);
            dropdownNode.setAttribute("success", "");
            await new Promise((resolve) => setTimeout(resolve, 2000));
        } catch (error) {
            dropdownNode.buttonText = buttonTextBefore;
            await Swal.fire({ icon: "error", title: neo__("Error", "Fehler"), text: error.message || neo__("Could not save the language.", "Sprache konnte nicht gespeichert werden.") });
        } finally {
            dropdownNode.disabled = false; dropdownNode.removeAttribute("success"); dropdownNode.buttonText = buttonTextBefore;
        }
    });
});

observeOnce("#neo-ai--custom-prompt-settings", async (root) => {
    await domLoaded();
    const openMediaPopup = () => new Promise(resolve => {
        const mediaFrame = wp.media({ title: neo__("Select test image", "Testbild auswählen"), button: { text: neo__("Use image", "Bild verwenden") }, library: { type: "image" }, multiple: false });
        mediaFrame.on("select", () => resolve(mediaFrame.state().get("selection").first()?.toJSON() ?? null));
        mediaFrame.on("close", () => setTimeout(() => resolve(null), 0));
        mediaFrame.open();
    });
    const savePromptAddition = async (fieldNode, showSaveButtonState = true) => {
        const promptType = fieldNode.getAttribute("data-prompt-type");
        const textareaNode = fieldNode.querySelector("textarea");
        const saveButtonNode = fieldNode.querySelector("[data-action=\"save\"]");
        textareaNode.disabled = true;
        if (showSaveButtonState) { saveButtonNode.setAttribute("loading", ""); saveButtonNode.removeAttribute("success"); }
        try { const response = await fetchEndpoint("/wp-json/neo/ai-custom-prompt-additions-save", { method: "POST", body: { "prompt-type": promptType, value: textareaNode.value } }).then(extractJson); textareaNode.value = response.value; if (showSaveButtonState) { saveButtonNode.setAttribute("success", ""); } return true; }
        catch (error) { await Swal.fire({ icon: "error", title: neo__("Error", "Fehler"), text: error.message || neo__("Could not save the prompt instructions.", "Prompt-Anweisungen konnten nicht gespeichert werden.") }); return false; }
        finally { textareaNode.disabled = false; if (showSaveButtonState) { saveButtonNode.removeAttribute("loading"); setTimeout(() => saveButtonNode.removeAttribute("success"), 2000); } }
    };
    for (const fieldNode of root.querySelectorAll(".neo-ai--custom-prompt-field")) {
        const promptType = fieldNode.getAttribute("data-prompt-type");
        const textareaNode = fieldNode.querySelector("textarea");
        const saveButtonNode = fieldNode.querySelector("[data-action=\"save\"]");
        const testButtonNode = fieldNode.querySelector("[data-action=\"test\"]");
        const syncTextareaHeight = () => { textareaNode.style.height = "auto"; textareaNode.style.height = `${Math.min(textareaNode.scrollHeight, 320)}px`; };
        textareaNode.addEventListener("input", syncTextareaHeight);
        saveButtonNode.addEventListener("click", async () => await savePromptAddition(fieldNode));
        testButtonNode.addEventListener("click", async () => {
            const selectedImage = await openMediaPopup();
            if (!selectedImage) { return; }
            testButtonNode.setAttribute("loading", "");
            try { if (!await savePromptAddition(fieldNode, false)) { return; } const generatedText = await (await neoLoadInterfaceFunc("neo-rename", "neo-ai--image-text-generation.js", "interfaceGenerateImageText20260713"))({ imageUrl: selectedImage.url, textType: promptType, imageTitle: selectedImage.title || "", imageAltText: selectedImage.alt || "" }); if (generatedText === null) { return; } await Swal.fire({ icon: "success", title: neo__("Prompt applied successfully", "Prompt erfolgreich übernommen"), text: (promptType === "title" ? neo__("Title preview: %s", "Titel-Vorschau: %s") : neo__("Alt text preview: %s", "Alttext-Vorschau: %s")).replace("%s", () => generatedText), confirmButtonText: neo__("OK", "OK") }); }
            finally { testButtonNode.removeAttribute("loading"); }
        });
        syncTextareaHeight();
    }
});
