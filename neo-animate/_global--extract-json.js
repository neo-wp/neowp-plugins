import { neoWarn } from "./_global--log.js";

export function extractJson(fetchResponseOrString) {
    if (typeof fetchResponseOrString.text === "function" && typeof fetchResponseOrString.json === "function") {
        return (async () => {
            try {
                const parsedJson = await fetchResponseOrString.text().then(extractJson);
                if (!fetchResponseOrString.ok) {
                    if (typeof parsedJson === "object" && parsedJson?.message) {
                        const err = new Error(String(parsedJson.message ?? "")); err.data = { ...(parsedJson.data ?? {}), code: parsedJson.code ?? parsedJson.data?.code }; err.debugMessage = err.data.debugMessage ?? parsedJson.message; err.fullMessage = err.debugMessage; throw err;
                    }
                    throw new Error(`REST API error ${fetchResponseOrString.status}: ${JSON.stringify(parsedJson)}`);
                }
                return parsedJson;
            } catch (error) { error.data = { ...(error.data ?? {}), responseUrl: fetchResponseOrString.url, responseStatus: fetchResponseOrString.status, responseRedirected: fetchResponseOrString.redirected, responseContentType: fetchResponseOrString.headers.get("Content-Type") ?? "" }; error.debugMessage = `REST response URL: ${fetchResponseOrString.url}; status: ${fetchResponseOrString.status}; redirected: ${fetchResponseOrString.redirected}; content type: ${error.data.responseContentType}. ${error.debugMessage ?? error.message}`; error.fullMessage = error.debugMessage; neoWarn(error.debugMessage); throw error; }
        })();
    }

    let parsedJson = null;
    let jsonStartIndex = -1;
    let match;
         if (fetchResponseOrString.endsWith("false"))                                     { parsedJson = false;            jsonStartIndex = fetchResponseOrString.length - "false".length;  }
    else if (fetchResponseOrString.endsWith("true"))                                      { parsedJson = true;             jsonStartIndex = fetchResponseOrString.length - "true".length;   }
    else if (fetchResponseOrString.endsWith("null"))                                      { parsedJson = null;             jsonStartIndex = fetchResponseOrString.length - "null".length;   }
    else if (match = fetchResponseOrString.match(/(-?(?:\d+\.\d+|\d+)(?:e[+\-]?\d+))$/i)) { parsedJson = Number(match[1]); jsonStartIndex = fetchResponseOrString.length - match[1].length; }
    else if (match = fetchResponseOrString.match(/(-?\d+(?:\.\d+)?)$/))                   { parsedJson = Number(match[1]); jsonStartIndex = fetchResponseOrString.length - match[1].length; }
    else {
        for (let i = 0; i < fetchResponseOrString.length; i++) {
            if (["{", "[", '"'].includes(fetchResponseOrString[i])) {
                try { parsedJson = JSON.parse(fetchResponseOrString.substring(i)); jsonStartIndex = i; break; } catch {}
            }
        }
    }

    if (jsonStartIndex === -1) { throw new Error(`No JSON found in REST response: ${fetchResponseOrString}`); }

    const phpWarningString = fetchResponseOrString.substring(0, jsonStartIndex);
    if (phpWarningString) { console.warn(`PHP warning from REST endpoint: ${phpWarningString}`); }

    return parsedJson;
}
