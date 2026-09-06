export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoOptimize"] || window["neoGlobalEnqueueFrontendNeoOptimize"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoOptimize"]  && window["neoGlobalEnqueueBackendNeoOptimize"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoOptimize"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoOptimize"] && window["neoGlobalEnqueueFrontendNeoOptimize"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoOptimize"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoOptimize"] || window["neoGlobalEnqueueFrontendNeoOptimize"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoOptimize"] || window["neoGlobalEnqueueFrontendNeoOptimize"])?.[variableName] !== undefined;
}
