import { addQueryParams } from "./_global--url-helper.js";
import { extractJson } from "./_global--extract-json.js";
import { jsVar, jsVarExists } from "./_global--enqueue-loader.js";
import { addEventListenerWithInitialCallMultiple } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";

const nonceRefreshIntervalMs = (1) * 60 * 60 * 1000;
const nonceRefreshTimeoutMs = 10 * 1000;
export async function refreshNonceIfOutdated(forceRefresh = false) {
    if (!(jsVarExists("neoGlobalRestEndpointNonce") && jsVarExists("neoGlobalRestEndpointAjaxUrl"))) { return; }
    if (!forceRefresh && !fetchEndpoint.nonceRefreshPromise && !(Date.now() - (refreshNonceIfOutdated.nonceLastRefreshAt ?? 0) >= nonceRefreshIntervalMs)) { return; }
    refreshNonceIfOutdated.nonceLastRefreshAt ??= Date.now();
    fetchEndpoint.nonceRefreshPromise ??= (async () => {
        refreshNonceIfOutdated.nonceLastRefreshAt = Date.now();

        const ajaxUrl = new URL(jsVar("neoGlobalRestEndpointAjaxUrl"), location.origin);
        ajaxUrl.searchParams.set("action", "rest-nonce");
        const abortController = new AbortController();

        const timeoutId = setTimeout(() => abortController.abort(), nonceRefreshTimeoutMs);
        try {
            const resp = await fetch(ajaxUrl.toString(), { credentials: "same-origin", signal: abortController.signal });
            if (!resp.ok) { throw new Error(`Failed to refresh nonce: HTTP ${resp.status}`); }
            const refreshedNonce = await resp.text();
            if (!/^[a-f0-9]{10}$/.test(refreshedNonce)) { throw new Error("Failed to refresh nonce: Invalid nonce response."); }
            fetchEndpoint.nonce = refreshedNonce;
        } finally { clearTimeout(timeoutId); }
    })();

    try { await fetchEndpoint.nonceRefreshPromise; } catch (error) { refreshNonceIfOutdated.nonceLastRefreshAt = Date.now() - nonceRefreshIntervalMs - 1000; throw error; } finally { fetchEndpoint.nonceRefreshPromise = null; }
}

setInterval(async () => {
    try { await refreshNonceIfOutdated(); } catch (error) {}
}, nonceRefreshIntervalMs);

addEventListenerWithInitialCallMultiple([[window, "focus"], [document, "visibilitychange"], [window, "pageshow"], [window, "online"]], async () => { if (document.visibilityState === "hidden") { return; } try { await refreshNonceIfOutdated(); } catch (error) {} });

export async function fetchEndpoint(path, options = {}) {
    if (!path.startsWith("/wp-json/neo/")) { throw new Error("The endpoint path must start with '/wp-json/neo/'."); }
    let { url, isPublic } = jsVar("neoGlobalRestEndpoints")[path] ?? {};
    if (!url) { throw new Error(`The endpoint '${path}' is not registered or available.`); }
    options = { ...options };
    options.headers ??= {}; options.headers["Accept"] ??= "application/json";
    if (options.query) {
        url = addQueryParams(url, options.query);
        delete options.query;
    }
    const suppressHttpCodeError = Boolean(options.suppressHttpCodeError); if (options.suppressHttpCodeError) { delete options.suppressHttpCodeError; }
    const timeoutSeconds = options.timeout ?? null; const timeoutMessage = options.timeoutMessage ?? `Endpoint request timed out after ${timeoutSeconds} seconds.`; delete options.timeout; delete options.timeoutMessage;
    if (timeoutSeconds !== null && !(Number.isFinite(timeoutSeconds) && timeoutSeconds > 0)) { throw new Error("The endpoint timeout must be a positive number of seconds."); }
    if (options.body && !(options.body instanceof FormData)) { options.headers ??= {}; options.headers["Content-Type"] = "application/json"; if (typeof options.body === "object") { options.body = JSON.stringify(options.body); } }
    if (!isPublic) { if (!jsVarExists("neoGlobalRestEndpointNonce")) { throw new Error("Nonce not found for endpoint call when trying to fetch " + path + ". Are you using a protected endpoint in the frontend as a non-admin?"); } fetchEndpoint.nonce ??= jsVar("neoGlobalRestEndpointNonce"); if (refreshNonceIfOutdated) { try { await refreshNonceIfOutdated(); } catch (error) {} } }
    if (!isPublic) { options.headers ??= {}; options.headers["X-WP-Nonce"] = fetchEndpoint.nonce; }
    const externalSignal = options.signal; const abortController = timeoutSeconds !== null ? new AbortController() : null; let timeoutId = null; let didTimeout = false;
    const abortFromExternalSignal = () => abortController.abort(externalSignal.reason);
    if (abortController) { if (externalSignal?.aborted) { abortFromExternalSignal(); } else { externalSignal?.addEventListener("abort", abortFromExternalSignal, { once: true }); } options.signal = abortController.signal; timeoutId = setTimeout(() => { if (!abortController.signal.aborted) { didTimeout = true; abortController.abort(); } }, timeoutSeconds * 1000); }
    try {
        let resp = await fetch(url, options);
        if (!isPublic && resp.status === 401 && (await resp.clone().text()).includes("invalid_nonce")) { await refreshNonceIfOutdated(true); options.headers["X-WP-Nonce"] = fetchEndpoint.nonce; resp = await fetch(url, options); }
        const responseContentType = resp.headers.get("Content-Type") ?? "";
        if (responseContentType.toLowerCase().includes("text/html")) {
            let errorMessage = neo__("The website returned a web page instead of the expected plugin data. Please try again. If the problem persists, contact your hosting provider.", "Die Website hat eine Webseite statt der erwarteten Plugin-Daten zurückgegeben. Bitte versuche es erneut. Falls das Problem weiterhin auftritt, kontaktiere deinen Hosting-Anbieter.");
            if (resp.redirected) { errorMessage = neo__("The website redirected the plugin request to a web page instead of returning data. Please try again. If the problem persists, contact your hosting provider.", "Die Website hat die Plugin-Anfrage auf eine Webseite weitergeleitet, statt Daten zurückzugeben. Bitte versuche es erneut. Falls das Problem weiterhin auftritt, kontaktiere deinen Hosting-Anbieter."); }
            const error = new Error(errorMessage);
            error.data = { code: resp.redirected ? "neo-global__rest-redirected-to-html" : "neo-global__rest-html-response", requestedUrl: url, responseUrl: resp.url, responseStatus: resp.status, responseRedirected: resp.redirected, responseContentType };
            error.debugMessage = `REST endpoint returned HTML. Requested URL: ${url}; response URL: ${resp.url}; status: ${resp.status}; redirected: ${resp.redirected}; content type: ${responseContentType}.`;
            throw error;
        }
        if (!suppressHttpCodeError && !resp.ok) {
            await extractJson(resp);
        }
        return resp;
    } catch (error) { if (didTimeout) { throw new Error(timeoutMessage); } throw error; }
    finally { if (timeoutId !== null) { clearTimeout(timeoutId); } externalSignal?.removeEventListener("abort", abortFromExternalSignal); }
}
