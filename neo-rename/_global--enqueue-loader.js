export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoRename"] || window["neoGlobalEnqueueFrontendNeoRename"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoRename"]  && window["neoGlobalEnqueueBackendNeoRename"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoRename"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoRename"] && window["neoGlobalEnqueueFrontendNeoRename"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoRename"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoRename"] || window["neoGlobalEnqueueFrontendNeoRename"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoRename"] || window["neoGlobalEnqueueFrontendNeoRename"])?.[variableName] !== undefined;
}
