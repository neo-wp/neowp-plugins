import { jsVar } from "./_global--enqueue-loader.js";
import { neo__ } from "./_global--translation.js";
import { neoWarn } from "./_global--log.js";

// Clean and verifiable interface architecture ensuring plugin compatibility modularity in WordPress. When changing a function, its signature has to be updated so that the function name includes the last change date, e.g. interfaceOpenRenameDialog20250813
export function neoInterfaceFunctionErrorMessage() { return neo__("neoWP: Incompatible function call. Make sure that all of your neoPlugins are up to date.", "neoWP: Inkompatibler Interface-Aufruf. Stelle sicher, dass alle deine neoPlugins aktuell sind."); }
export function isInterfaceFunctionErrorMessage(message) { return message === neoInterfaceFunctionErrorMessage(); }
export async function neoLoadInterfaceFunc(callingPluginSlug, importPath, functionName) {
    const interfaceModuleUrls = jsVar("neoInterfaceModuleUrls");
    let moduleUrl = null; for (const [baseUrl, jsRelPaths] of Object.entries(interfaceModuleUrls)) { if (jsRelPaths.includes(importPath)) { moduleUrl = baseUrl + "/" + importPath; break; } }

    const warnAboutIncompatiblePlugin = async (reason) => {
        if (functionName.toLowerCase().includes("suppressErrorPopup".toLowerCase())) { return; }
        neoWarn(`neoWP: Incompatible function call in plugin "${callingPluginSlug}". The function "${functionName}" is not available. Reason: ${reason || "Function or module not found."} Make sure that all of your neoPlugins are up to date.`);
        const Swal = (await import("./_global-sweetalert2.js")).default;
        Swal.fire({
            icon: "warning", title: neo__("Incompatible neoPlugin", "Nicht kompatibles neoPlugin"),
            text: neo__(`The neoPlugin that provides this feature is not compatible with the current version of ${callingPluginSlug}. You can use this feature once all your neoPlugins are up to date. Update all neoPlugins.`, `Das neoPlugin, das diese Funktion bereitstellt, ist nicht mit der aktuellen Version von ${callingPluginSlug} kompatibel. Du kannst dieses Feature verwenden, sobald alle deine neoPlugins auf dem aktuellen Stand sind. Aktualisiere alle neoPlugins.`),
            showCloseButton: true, showConfirmButton: true, showDenyButton: false,
            confirmButtonText: neo__("OK", "OK"),
        });
    };

    if (!moduleUrl) { warnAboutIncompatiblePlugin(); throw new Error(neoInterfaceFunctionErrorMessage()); }

    const module = await import(moduleUrl);

    if (!module[functionName]) { warnAboutIncompatiblePlugin(); throw new Error(neoInterfaceFunctionErrorMessage()); }

    return module[functionName];
}

export function isModuleAvailable(moduleName) {
    return jsVar("neoAvailableModules").includes(moduleName);
}
