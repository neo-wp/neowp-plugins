export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoMotion"] || window["neoGlobalEnqueueFrontendNeoMotion"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoMotion"]  && window["neoGlobalEnqueueBackendNeoMotion"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoMotion"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoMotion"] && window["neoGlobalEnqueueFrontendNeoMotion"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoMotion"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoMotion"] || window["neoGlobalEnqueueFrontendNeoMotion"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoMotion"] || window["neoGlobalEnqueueFrontendNeoMotion"])?.[variableName] !== undefined;
}
