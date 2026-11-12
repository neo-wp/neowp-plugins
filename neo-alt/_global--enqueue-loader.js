export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoAlt"] || window["neoGlobalEnqueueFrontendNeoAlt"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoAlt"]  && window["neoGlobalEnqueueBackendNeoAlt"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoAlt"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoAlt"] && window["neoGlobalEnqueueFrontendNeoAlt"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoAlt"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoAlt"] || window["neoGlobalEnqueueFrontendNeoAlt"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoAlt"] || window["neoGlobalEnqueueFrontendNeoAlt"])?.[variableName] !== undefined;
}
