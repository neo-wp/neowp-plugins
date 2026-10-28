export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoDuplicate"] || window["neoGlobalEnqueueFrontendNeoDuplicate"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoDuplicate"]  && window["neoGlobalEnqueueBackendNeoDuplicate"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoDuplicate"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoDuplicate"] && window["neoGlobalEnqueueFrontendNeoDuplicate"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoDuplicate"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoDuplicate"] || window["neoGlobalEnqueueFrontendNeoDuplicate"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoDuplicate"] || window["neoGlobalEnqueueFrontendNeoDuplicate"])?.[variableName] !== undefined;
}
