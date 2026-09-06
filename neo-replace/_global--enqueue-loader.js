export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoReplace"] || window["neoGlobalEnqueueFrontendNeoReplace"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoReplace"]  && window["neoGlobalEnqueueBackendNeoReplace"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoReplace"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoReplace"] && window["neoGlobalEnqueueFrontendNeoReplace"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoReplace"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoReplace"] || window["neoGlobalEnqueueFrontendNeoReplace"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoReplace"] || window["neoGlobalEnqueueFrontendNeoReplace"])?.[variableName] !== undefined;
}
