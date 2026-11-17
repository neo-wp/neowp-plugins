export function jsVar(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoAnimate"] || window["neoGlobalEnqueueFrontendNeoAnimate"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    if (window["neoGlobalEnqueueBackendNeoAnimate"]  && window["neoGlobalEnqueueBackendNeoAnimate"][variableName]  !== undefined) { return window["neoGlobalEnqueueBackendNeoAnimate"][variableName]; }
    if (window["neoGlobalEnqueueFrontendNeoAnimate"] && window["neoGlobalEnqueueFrontendNeoAnimate"][variableName] !== undefined) { return window["neoGlobalEnqueueFrontendNeoAnimate"][variableName]; }
    throw new Error("Missing JS variable: " + variableName + ". Make sure it is registered via enqueue_js_variable_backend(...) or enqueue_js_variable_frontend(...).");
}

export function jsVarExists(variableName) {
    if (!(window["neoGlobalEnqueueBackendNeoAnimate"] || window["neoGlobalEnqueueFrontendNeoAnimate"])) { throw new Error("The neoWP JS variables have not been initialized yet. Problem with the enqueue global?"); }
    return (window["neoGlobalEnqueueBackendNeoAnimate"] || window["neoGlobalEnqueueFrontendNeoAnimate"])?.[variableName] !== undefined;
}
