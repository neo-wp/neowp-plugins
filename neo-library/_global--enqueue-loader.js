export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoLibrary"] || window["neoGlobalEnqueueFrontendNeoLibrary"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoLibrary"]  && window["neoGlobalEnqueueBackendNeoLibrary"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoLibrary"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoLibrary"] && window["neoGlobalEnqueueFrontendNeoLibrary"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoLibrary"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoLibrary"] || window["neoGlobalEnqueueFrontendNeoLibrary"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoLibrary"] || window["neoGlobalEnqueueFrontendNeoLibrary"])?.[variableName] !== undefined;
}
