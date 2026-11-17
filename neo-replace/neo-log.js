import { jsVar } from "./_global--enqueue-loader.js";
import { neo__ } from "./_global--translation.js";
import { fetchEndpoint } from "./_global--endpoint.js";

const getNeoLogMessage = () => {
    let versionNumber; try { versionNumber = jsVar("neoLogVersionNumber"); } catch { versionNumber = "<unknown>"; }
    return neo__(
        `🙋‍♂️ The neoWP team is strongly committed to improving neoPlugins. We appreciate your bug reports. For a quick resolution, please report the issue with a screenshot and the version number ${versionNumber}. You'll hear from us shortly. Thank you! 💌 urgent@neo-wp.com 🎁`,
        `🙋‍♂️ Das neoWP-Team setzt sich stark für die Verbesserung der neoPlugins ein. Wir schätzen deine Fehlerberichte. Für eine schnelle Lösung melde bitte das Problem mit einem Screenshot und der Versionsnummer ${versionNumber}. Du hörst in Kürze von uns. Danke! 💌 urgent@neo-wp.com 🎁`,
    );
};

const neoLogEndpointSentAtTimestamps = [];
let neoLogEndpointSentTotal = 0;
const sendNeoLogToEndpoint = async (type, message) => {
    try {
        if (!fetchEndpoint.nonce && !window["neoGlobalEnqueueBackendNeoReplace"]?.neoGlobalRestEndpointNonce && !window["neoGlobalEnqueueFrontendNeoReplace"]?.neoGlobalRestEndpointNonce) { return; }
        const now = Date.now();
        while (neoLogEndpointSentAtTimestamps[0] && now - neoLogEndpointSentAtTimestamps[0] > 60000) { neoLogEndpointSentAtTimestamps.shift(); }
        if (neoLogEndpointSentTotal >= 100 || neoLogEndpointSentAtTimestamps.length >= 10) { return; }
        neoLogEndpointSentAtTimestamps.push(now); neoLogEndpointSentTotal++;
        await fetchEndpoint("/wp-json/neo/log-js", {
            method: "POST",
            body: {
                type,
                message: message.map((value) => {
                    const seenObjects = new WeakSet();
                    let serializedValue = "";
                    try {
                        serializedValue = typeof value === "string" ? value : JSON.stringify(value instanceof Error ? { name: value.name, debugMessage: value.debugMessage, data: value.data, message: value.message, stack: value.stack } : value, (key, nestedValue) => {
                            if (typeof nestedValue === "object" && nestedValue !== null) {
                                if (seenObjects.has(nestedValue)) { return "[Circular]"; }
                                seenObjects.add(nestedValue);
                            }
                            if (typeof nestedValue === "function") { return `[Function ${nestedValue.name || "anonymous"}]`; }
                            return nestedValue;
                        });
                    } catch (error) {
                        serializedValue = String(value);
                    }
                    serializedValue = serializedValue ?? String(value);
                    return serializedValue.length > 300 ? serializedValue.slice(0, 300) + "..." : serializedValue;
                }),
                url: location.href,
                "user-agent": navigator.userAgent,
            },
            suppressHttpCodeError: true,
        });
    } catch (error) { }
};
const isNeoRelatedError = (error) => {
    return (typeof error === "string" ? error : ((error?.stack || "") + (error?.message || "") + (error?.filename || ""))).split(location.hostname).join("").includes("neo");
};

window.addEventListener("error", async (event) => {
    await new Promise(requestAnimationFrame);
    const error = event.error || { message: event.message, filename: event.filename, lineno: event.lineno, colno: event.colno };
    if (!isNeoRelatedError(error)) { return; }
    sendNeoLogToEndpoint("error", ["Global JS error", error]);
});
window.addEventListener("unhandledrejection", async (event) => {
    await new Promise(requestAnimationFrame);
    if (!isNeoRelatedError(event.reason)) { return; }
    sendNeoLogToEndpoint("error", ["Unhandled JS rejection", event.reason]);
});

window.NeoLogWarnError = {
    neoWarn: function (...message)  { console.warn(...message,  getNeoLogMessage()); sendNeoLogToEndpoint("warn", message); },
    neoError: function (...message) { console.error(...message, getNeoLogMessage()); sendNeoLogToEndpoint("error", message); },
};
