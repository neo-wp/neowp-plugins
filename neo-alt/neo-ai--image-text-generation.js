import { fetchEndpoint } from "./_global--endpoint.js";
import { extractJson } from "./_global--extract-json.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { neoError, neoWarn } from "./_global--log.js";
import { imageToWebpFile, videoFrameToWebpFile } from "./_global-image-to-webp-converter.js";
import { pdfFirstPageToWebpFile } from "./_global-pdf-to-webp-converter.js";
import { removeAllQueryParams, removeUrlFragment, stripProtocol } from "./_global--url-helper.js";
import { getWebsiteHostType } from "./_global--website-host-type.js";
import { neo__ } from "./_global--translation.js";
import { pricingUrl } from "./_global-pricing-url.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";

import { getFileType } from "./_global-media-file-type.js";
import { DomNodeHelper, escapeHtml } from "./_global--dom-node-helper.js";
import { getNeoAiConnectionProvider } from "./neo-ai.js";
import Swal from "./_global-sweetalert2.js";

const generatedImageTexts = new Map();
const imageContextSnippetsPromises = new Map();
const pageContextHtmlByUrl = new Map(); const maxCachedPageContextCount = 20;
let lowestRemainingRequests = null;
let pageContextFetchActive = false;
const contextRequestTimeoutSeconds = 12; const pageContextTimeoutSeconds = 8; const cumulativePageContextTimeoutSeconds = 30; const generationRequestTimeoutSeconds = 290;
function getGenerationStateKey(imageUrl, textType) { return `${textType}|${removeUrlFragment(removeAllQueryParams(imageUrl))}`; }

async function runSerialPageContextFetch(callback) {
    while (pageContextFetchActive) { await new Promise(resolve => setTimeout(resolve, 25)); }
    pageContextFetchActive = true;
    try { return await callback(); }
    finally { pageContextFetchActive = false; }
}

async function showAiSetupMissingDialog(message, swalContainerClass) {
    const aiSettingsUrl = jsVar("neoAiSettingsSectionUrl");
    const result = await Swal.fire({ customClass: { container: swalContainerClass }, icon: "warning", title: neo__("AI setup missing", "AI-Einrichtung fehlt"), text: message || neo__("Add an OpenAI API key or configure a WordPress AI provider.", "Hinterlege einen OpenAI API-Key oder konfiguriere einen WordPress-AI-Provider."), showCancelButton: !!aiSettingsUrl, confirmButtonText: aiSettingsUrl ? neo__("Open neoAI settings", "neoAI-Einstellungen öffnen") : neo__("OK", "OK"), cancelButtonText: neo__("OK", "OK") });
    if (aiSettingsUrl && result.isConfirmed) { window.open(aiSettingsUrl, "_blank", "noopener"); }
}

export async function showUnsupportedFreeProviderEnvironmentDialog(swalContainerClass = "") {
    const websiteHostType = getWebsiteHostType();
    if (websiteHostType === "domain") { return false; }
    const environmentTitle = websiteHostType === "playground" ? neo__("neoAI is not available in sandbox", "neoAI ist in der Sandbox nicht verfügbar") : neo__("neoAI is not available here", "neoAI ist hier nicht verfügbar");
    const environmentMessage = websiteHostType === "playground" ? neo__("The free neoAI provider is not available in WordPress Playground. You can use neoAI with free credits on a regular WordPress website.", "Der kostenfreie neoAI Provider ist im WordPress Playground nicht verfügbar. Auf einer regulären WordPress-Website kannst du neoAI mit kostenfreien Credits nutzen.") : (websiteHostType === "localhost" ? neo__("The free neoAI provider is not available on localhost. You can use neoAI with free credits on a publicly accessible WordPress website.", "Der kostenfreie neoAI Provider ist auf localhost nicht verfügbar. Auf einer öffentlich erreichbaren WordPress-Website kannst du neoAI mit kostenfreien Credits nutzen.") : neo__("The free neoAI provider is not available when WordPress uses an IP address. You can use neoAI with free credits on a publicly accessible WordPress website with its own domain.", "Der kostenfreie neoAI Provider ist nicht verfügbar, wenn WordPress über eine IP-Adresse läuft. Auf einer öffentlich erreichbaren WordPress-Website mit eigener Domain kannst du neoAI mit kostenfreien Credits nutzen."));
    await Swal.fire({ customClass: { container: swalContainerClass }, icon: "info", title: environmentTitle, text: environmentMessage, confirmButtonText: neo__("OK", "OK") });
    return true;
}

export async function interfaceShowUnsupportedFreeProviderEnvironmentDialog20260729(swalContainerClass = "") {
    return getNeoAiConnectionProvider() === "neoai" && await showUnsupportedFreeProviderEnvironmentDialog(swalContainerClass);
}

export function interfaceClearGeneratedImageTexts20260713({ imageUrl = "", textType = "" } = {}) {
    if (imageUrl === "" || !["title", "alt"].includes(textType)) { throw new Error("imageUrl and textType title or alt are required."); }
    generatedImageTexts.delete(getGenerationStateKey(imageUrl, textType));
}

export async function interfaceGenerateImageText20260713({ imageUrl = "", textType = "", swalContainerClass = "", nearbyTitles = [], imageTitle = null, imageAltText = null, promptAddition = "", bulkConfirmationState = null, errorHandler = null } = {}) {
    if (imageUrl === "" || !["title", "alt"].includes(textType)) { throw new Error("imageUrl and textType title or alt are required."); }

    if (getNeoAiConnectionProvider() === "neoai" && await showUnsupportedFreeProviderEnvironmentDialog(swalContainerClass)) { return null; }
    const mediaFileType = getFileType(imageUrl);
    if (!(["image", "video", "pdf", "txt"].includes(mediaFileType))) { await Swal.fire({ customClass: { container: swalContainerClass }, icon: "error", title: neo__("Unsupported media", "Nicht unterstütztes Medium"), text: neo__("AI generation is only available for images as well as video, PDF and text-file titles.", "AI-Generierung ist nur für Bilder sowie Video-, PDF- und Textdatei-Titel verfügbar.") }); return null; }

    const loadPopupCss = async () => {
        if (document.querySelector("link[data-neo-ai--css]")) { return; }
        const linkNode = new DomNodeHelper(`<link rel="stylesheet" href="${escapeHtml(pluginUrl() + "/neo-ai.css")}" data-neo-ai--css>`).getNode(); document.head.append(linkNode); await new Promise((resolve) => { linkNode.addEventListener("load", resolve, { once: true }); linkNode.addEventListener("error", resolve, { once: true }); });
    };

    const getImageContextSnippets = async () => {
        const contextBudget = 60000; const pageBudget = 30000; const snippets = []; const seenSnippets = new Set();
        let cumulativePageFetchDurationMilliseconds = 0;
        const normalizedImageUrl = removeUrlFragment(removeAllQueryParams(imageUrl));
        const imagePath = new URL(normalizedImageUrl, location.origin).pathname;
        const imageUrlNeedles = [...new Set([stripProtocol(normalizedImageUrl), imagePath, encodeURI(stripProtocol(normalizedImageUrl)), encodeURI(imagePath)].filter(Boolean))];
        let targetsResponse = null; try { targetsResponse = await runSerialPageContextFetch(() => fetchEndpoint("/wp-json/neo/ai-image-usage-context-targets", { method: "GET", query: { "image-url": normalizedImageUrl }, timeout: contextRequestTimeoutSeconds, timeoutMessage: "neoAI context lookup timed out." }).then(extractJson)); } catch (error) { neoError("neoAI context lookup failed:", error); }
        for (const target of targetsResponse?.targets ?? []) {
            if (snippets.join("").length >= contextBudget) { break; }
            const remainingPageFetchDurationMilliseconds = cumulativePageContextTimeoutSeconds * 1000 - cumulativePageFetchDurationMilliseconds;
            if (!(remainingPageFetchDurationMilliseconds > 0)) { neoWarn(`neoAI page context fetch budget reached. Image URL: ${normalizedImageUrl}; last page URL: ${target.url}; post ID: ${target.postId ?? "unknown"}; cumulative fetch time: ${Math.round(cumulativePageFetchDurationMilliseconds)}ms.`); break; }
            const contextHeader = `## ${target.title}\nURL path: ${target.path === "/" ? "/ (homepage)" : target.path}`;
            let htmlText = ""; let abortController = null; let cumulativeTimeoutEndsCurrentFetch = false;
            try { htmlText = await runSerialPageContextFetch(async () => { const pageContextCacheKey = removeUrlFragment(target.url); if (pageContextHtmlByUrl.has(pageContextCacheKey)) { return pageContextHtmlByUrl.get(pageContextCacheKey); } const fetchTimeoutMilliseconds = Math.min(pageContextTimeoutSeconds * 1000, remainingPageFetchDurationMilliseconds); cumulativeTimeoutEndsCurrentFetch = fetchTimeoutMilliseconds === remainingPageFetchDurationMilliseconds; abortController = new AbortController(); const fetchStartTime = performance.now(); const timeoutId = setTimeout(() => abortController.abort(), fetchTimeoutMilliseconds); try { const response = await fetch(target.url, { credentials: "same-origin", signal: abortController.signal }); const fetchedHtmlText = response.ok ? await response.text() : ""; if (response.ok) { if (pageContextHtmlByUrl.size >= maxCachedPageContextCount) { pageContextHtmlByUrl.clear(); } pageContextHtmlByUrl.set(pageContextCacheKey, fetchedHtmlText); } return fetchedHtmlText; } finally { clearTimeout(timeoutId); cumulativePageFetchDurationMilliseconds += performance.now() - fetchStartTime; } }); } catch (error) { if (!abortController?.signal.aborted || !cumulativeTimeoutEndsCurrentFetch) { neoError("neoAI page HTML fetch failed:", abortController?.signal.aborted ? new Error(`neoAI page context request timed out. URL: ${target.url}; timeout: ${pageContextTimeoutSeconds}s; post ID: ${target.postId ?? "unknown"}; image URL: ${normalizedImageUrl}`) : error); } }
            if (abortController?.signal.aborted && cumulativeTimeoutEndsCurrentFetch) { neoWarn(`neoAI page context fetch budget reached. Image URL: ${normalizedImageUrl}; last page URL: ${target.url}; post ID: ${target.postId ?? "unknown"}; cumulative fetch time: ${Math.round(cumulativePageFetchDurationMilliseconds)}ms.`); break; }
            const bodyNode = new DOMParser().parseFromString(htmlText, "text/html").body;
            bodyNode.querySelectorAll("img").forEach(imgNode => { if ([...imgNode.attributes].some(attribute => imageUrlNeedles.some(imageUrlNeedle => attribute.value.includes(imageUrlNeedle)))) { imgNode.replaceWith(document.createTextNode(" [[THE IMAGE IS HERE]] ")); } });
            bodyNode.querySelectorAll("script, style, img, header, footer, nav, aside, [role=\"banner\"], [role=\"contentinfo\"], [role=\"navigation\"], [role=\"complementary\"], .head, .site-head, .page-head, .masthead, .site-header, .page-header, .topbar, .navbar, .menu, .breadcrumb, .sidebar, .widget, .cookie, .popup, .modal, .newsletter, .social, .share, .pagination, .related, .comments, #wpadminbar, #header, #footer, #sidebar, #comments").forEach(node => node.remove());
            let snippet = bodyNode.innerText || "";
            snippet = snippet.replaceAll("Skip to main content", "").replaceAll("Skip to footer", "");
            snippet = snippet.replace(/\s+/g, " ").trim().slice(0, pageBudget);
            if (snippet === "") { snippets.push(contextHeader); continue; }
            if (seenSnippets.has(snippet)) { continue; }
            seenSnippets.add(snippet); snippets.push(contextHeader + "\n" + snippet);
        }
        let usedChars = 0; return snippets.filter(snippet => { usedChars += snippet.length; return usedChars <= contextBudget; });
    };

    let imagePreviewFilePromise = null;

    const confirmFreeProviderRequest = async ({ prompt, confirmedImageUrl }) => {
        if (!bulkConfirmationState && localStorage.getItem("neoAiFreeProviderConfirmationHidden") === "1") { return true; }
        if (bulkConfirmationState?.hidden) { return true; }
        const previousBulkConfirmation = bulkConfirmationState?.confirmationQueue; let resolveBulkConfirmation = null;
        if (bulkConfirmationState) { bulkConfirmationState.confirmationQueue = new Promise(resolve => resolveBulkConfirmation = resolve); }
        if (previousBulkConfirmation && !await previousBulkConfirmation) { resolveBulkConfirmation(false); return false; }
        if (bulkConfirmationState?.hidden) { resolveBulkConfirmation(true); return true; }
        let mediaPreviewObjectUrl = "";
        try {
            if (bulkConfirmationState && localStorage.getItem("neoAiFreeProviderConfirmationHidden") === "1") { bulkConfirmationState.hidden = true; resolveBulkConfirmation(true); return true; }
            await loadPopupCss();
            const bulkRequestCountText = bulkConfirmationState ? `<strong>${escapeHtml(neo__("Planned free AI requests for this bulk generation: %s", "Geplante kostenfreie AI-Anfragen für diese Bulk-Generierung: %s").replace("%s", bulkConfirmationState.requestCount.toLocaleString()))}</strong>` : "";
            if (["video", "pdf"].includes(mediaFileType)) { mediaPreviewObjectUrl = URL.createObjectURL(await imagePreviewFilePromise); }
            const exampleImageLabel = bulkConfirmationState ? `<small class="neo-ai--free-provider-example-label">${escapeHtml(neo__("Example image", "Beispielbild"))}</small>` : ""; const examplePromptLabel = bulkConfirmationState ? `<small class="neo-ai--free-provider-example-label">${escapeHtml(neo__("Example prompt", "Beispiel-Prompt"))}</small>` : ""; const mediaPreviewHtml = mediaFileType === "txt" ? `<code>${escapeHtml(confirmedImageUrl)}</code>` : `<img src="${escapeHtml(mediaPreviewObjectUrl || confirmedImageUrl)}" alt="">`;
            const contentNode = new DomNodeHelper(`<div class="neo-ai--free-provider-popup">${bulkRequestCountText}<div class="neo-ai--free-provider-example">${exampleImageLabel}${mediaPreviewHtml}</div><div class="neo-ai--free-provider-example">${examplePromptLabel}<details><summary>${escapeHtml(neo__("Prompt to be sent", "Gesendeten Prompt anzeigen"))}</summary><pre>${escapeHtml(prompt)}</pre></details></div><small>${escapeHtml(neo__("Media content, prompt, and page context are sent through neoAI to OpenAI in the USA. Do not send personal or confidential data.", "Medieninhalt, Prompt und Seitenkontext werden über neoAI an OpenAI in den USA gesendet. Sende keine personenbezogenen oder vertraulichen Daten."))} <a href="${escapeHtml(neo__("https://neo-wp.com/privacy-policy-plugins/?ref=neo-ai--consent", "https://neo-wp.com/de/privacy-policy-plugins/?ref=neo-ai--consent"))}" target="_blank" rel="noopener">${escapeHtml(neo__("Privacy details", "Datenschutzdetails"))}</a></small><label><input type="checkbox" data-neo-ai--hide-permanently><span>${escapeHtml(neo__("Do not show again", "Nicht mehr anzeigen"))}</span></label></div>`).getNode();
            const hideCheckboxNode = contentNode.querySelector("[data-neo-ai--hide-permanently]");
            const result = await Swal.fire({ title: neo__("Confirm free AI request", "Kostenfreie AI-Anfrage bestätigen"), html: contentNode, showCancelButton: true, confirmButtonText: bulkConfirmationState ? neo__("Send %s free AI requests", "%s kostenfreie AI-Anfragen senden").replace("%s", bulkConfirmationState.requestCount.toLocaleString()) : neo__("Send free AI request", "Kostenfreie AI-Anfrage senden"), cancelButtonText: neo__("Cancel", "Abbrechen"), customClass: { container: `neo-ai--free-provider-swal ${swalContainerClass}` } });
            if (!result.isConfirmed) { if (bulkConfirmationState) { bulkConfirmationState.cancelled = true; } resolveBulkConfirmation?.(false); return false; }
            if (hideCheckboxNode.checked) { localStorage.setItem("neoAiFreeProviderConfirmationHidden", "1"); }
            if (bulkConfirmationState) { bulkConfirmationState.hidden = true; }
            resolveBulkConfirmation?.(true); return true;
        } catch (error) {
            resolveBulkConfirmation?.(false); throw error;
        } finally { if (mediaPreviewObjectUrl !== "") { URL.revokeObjectURL(mediaPreviewObjectUrl); } }
    };

    const showAiErrorDialog = async (error) => {
        if (error?.data?.code === "neo-ai__quota-exhausted") { await loadPopupCss(); const errorMessage = bulkConfirmationState ? neo__("Bulk generation was stopped because the free neoAI quota for this domain is exhausted. Suggestions generated so far remain available for review. Get neoMedia Pro for a higher quota, or configure your own API key.", "Die Bulk-Generierung wurde beendet, weil das kostenfreie neoAI Kontingent dieser Domain ausgeschöpft ist. Bereits generierte Vorschläge bleiben zur Prüfung erhalten. Mit neoMedia Pro erhältst du ein höheres Kontingent; alternativ kannst du einen eigenen API-Key hinterlegen.") : neo__("The free neoAI quota for this domain is exhausted. Get neoMedia Pro for a higher quota, or you can configure your own API key.", "Das kostenfreie neoAI Kontingent dieser Domain ist ausgeschöpft. Mit neoMedia Pro erhältst du ein höheres Kontingent; alternativ kannst du einen eigenen API-Key hinterlegen."); const contentNode = new DomNodeHelper(`<div class="neo-ai--quota-error-popup"><div>${escapeHtml(errorMessage)}</div></div>`).getNode(); contentNode.appendChild(new DomNodeHelper(`<div class="neo-ai--quota-error-pro"><neo-pro-crown-neo-alt></neo-pro-crown-neo-alt><span>${escapeHtml(neo__("Get 10x as much quota with Pro", "Mit Pro erhältst du 10x so viel Kontingent"))}</span></div>`).getNode());const result = await Swal.fire({ customClass: { container: swalContainerClass }, icon: "error", title: neo__("Free quota exhausted", "Kostenfreies Kontingent ausgeschöpft"), html: contentNode, showDenyButton: true, confirmButtonText: neo__("View Pro pricing", "Pro-Preise ansehen"), denyButtonText: neo__("Open neoAI settings", "neoAI-Einstellungen öffnen"), denyButtonColor: "var(--wp-admin-theme-color, #2271b1)" }); if (result.isConfirmed) { window.open(pricingUrl("neo-media"), "_blank", "noopener"); } if (result.isDenied) { window.open(jsVar("neoAiSettingsSectionUrl"), "_blank", "noopener"); } return; }
        if (error?.data?.code === "ai-setup-missing") { await showAiSetupMissingDialog(error.message, swalContainerClass); return; }
        const endpointErrorMessage = String(error?.message ?? "").trim();
        let errorMessage = textType === "title" ? neo__("Could not generate title.", "Titel konnte nicht generiert werden.") : neo__("Could not generate alt text.", "Alt-Text konnte nicht generiert werden.");
        if (endpointErrorMessage !== "") { errorMessage += " " + endpointErrorMessage; }
        if (error.message?.includes("The image data you provided does not represent a valid image")) { errorMessage = neo__("OpenAI could not read this image. Please try a valid JPG, PNG, GIF or WebP image.", "OpenAI konnte dieses Bild nicht lesen. Bitte nutze ein gültiges JPG-, PNG-, GIF- oder WebP-Bild."); }
        await Swal.fire({ customClass: { container: swalContainerClass }, icon: "error", title: neo__("AI error", "AI-Fehler"), text: errorMessage, confirmButtonText: neo__("OK", "OK") });
    };
    const stateKey = getGenerationStateKey(imageUrl, textType);
    try {
        const contextLookupKey = removeUrlFragment(removeAllQueryParams(imageUrl)); const contextSnippetsPromises = bulkConfirmationState ? (bulkConfirmationState.contextSnippetsPromises ??= new Map()) : imageContextSnippetsPromises; let contextSnippetsPromise = contextSnippetsPromises.get(contextLookupKey);
        if (!contextSnippetsPromise) { contextSnippetsPromise = (async () => { try { return await getImageContextSnippets(); } finally { if (!bulkConfirmationState) { imageContextSnippetsPromises.delete(contextLookupKey); } } })(); contextSnippetsPromises.set(contextLookupKey, contextSnippetsPromise); }

        const prepareEndpointBody = async (body) => {
            if (mediaFileType === "txt") { return body; }
            imagePreviewFilePromise ??= mediaFileType === "video" ? videoFrameToWebpFile(imageUrl, { fileName: "neo-ai--image-preview.webp" }) : (mediaFileType === "pdf" ? pdfFirstPageToWebpFile(imageUrl, { fileName: "neo-ai-image-preview.webp" }) : imageToWebpFile(imageUrl, { fileName: "neo-ai-image-preview.webp" })); let imagePreviewFile; try { imagePreviewFile = await imagePreviewFilePromise; } catch (error) { if (mediaFileType !== "image") { throw error; } neoError("neoAI image WebP conversion failed; using original image:", error); return body; }
            const formData = new FormData();
            for (const [key, value] of Object.entries(body)) { if (value === null || value === undefined) { continue; } if (Array.isArray(value)) { for (const item of value) { formData.append(key + "[]", item); } } else { formData.append(key, String(value)); } }
            formData.append("image-preview-file", imagePreviewFile, imagePreviewFile.name);
            return formData;
        };

        const requestGeneration = async (body) => {
            let requestAttempt = 0;
            while (true) {
                const endpointBody = await prepareEndpointBody(body);
                try { requestAttempt++; return await fetchEndpoint("/wp-json/neo/ai-generate-image-text", { method: "POST", body: endpointBody, timeout: generationRequestTimeoutSeconds, timeoutMessage: neo__("AI generation timed out. Please try again.", "Die AI-Generierung hat zu lange gedauert. Bitte versuche es erneut.") }).then(extractJson); }
                catch (error) { if (!(error instanceof TypeError)) { throw error; } if (!navigator.onLine) { await new Promise(resolve => window.addEventListener("online", resolve, { once: true })); continue; } if (requestAttempt >= 3) { throw error; } await new Promise(resolve => setTimeout(resolve, 3000)); }
            }
        };
        const requestBody = { "image-url": imageUrl, "text-type": textType, "nearby-titles": nearbyTitles, "image-title": imageTitle, "image-alt-text": imageAltText, "prompt-addition": promptAddition, "context-snippets": await contextSnippetsPromise, "previous-generations": generatedImageTexts.get(stateKey) ?? [] };
        let response = await requestGeneration(requestBody);
        if (response.confirmationRequired) { if (!await confirmFreeProviderRequest({ prompt: response.prompt, confirmedImageUrl: response.imageUrl })) { return null; } response = await requestGeneration({ ...requestBody, "free-provider-confirmed": true }); }
        if (response.ok === false) { if (errorHandler) { errorHandler(new Error(response.warning || "No AI connection available.")); } else { await showAiSetupMissingDialog(response.warning, swalContainerClass); } return null; }
        if (typeof response.text !== "string" || response.text === "") { throw new Error("neoAI returned no generated image text."); }
        generatedImageTexts.set(stateKey, [...(generatedImageTexts.get(stateKey) ?? []), response.text]);
        const remainingRequests = response.remaining_requests === undefined || response.remaining_requests === null ? null : Number(response.remaining_requests);
        const hasOpenSwalDialog = Swal.isVisible() && !document.querySelector(".swal2-popup")?.classList.contains("swal2-toast");
        if (Number.isFinite(remainingRequests) && (lowestRemainingRequests === null || remainingRequests < lowestRemainingRequests)) { lowestRemainingRequests = remainingRequests; if (!hasOpenSwalDialog) { await loadPopupCss(); Swal.fire({ toast: true, position: "bottom-start", icon: "info", title: neo__("You have %s free image generations left this month", "Dir stehen diesen Monat noch %s kostenfreie Bildgenerierungen zur Verfügung").replace("%s", remainingRequests.toLocaleString()), showConfirmButton: false, timer: 5000, timerProgressBar: true, customClass: { container: `${swalContainerClass} neo-ai--remaining-requests-toast` } }); } }
        return response.text;
    } catch (error) { if (mediaFileType === "video" && error?.name === "NotSupportedError") { return null; } neoError(error); if (errorHandler) { errorHandler(error); if (error?.data?.code === "neo-ai__quota-exhausted" && bulkConfirmationState && !bulkConfirmationState.quotaErrorDialogShown) { bulkConfirmationState.quotaErrorDialogShown = true; await showAiErrorDialog(error); } } else { await showAiErrorDialog(error); } return null; }
}
