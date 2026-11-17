export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoDraw"] || window["neoGlobalEnqueueFrontendNeoDraw"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoDraw"]  && window["neoGlobalEnqueueBackendNeoDraw"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoDraw"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoDraw"] && window["neoGlobalEnqueueFrontendNeoDraw"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoDraw"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoDraw"] || window["neoGlobalEnqueueFrontendNeoDraw"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoDraw"] || window["neoGlobalEnqueueFrontendNeoDraw"])?.[variableName] !== undefined;
}
