import { jsVar } from "./_global--enqueue-loader.js";
import { isModuleAvailable } from "./_global--interface.js";
import { reloadPage } from "./_global-reload-page.js";
import Swal from "./_global-sweetalert2.js";
import { neo__ } from "./_global--translation.js";

export async function requireNeoPluginForLibraryAction(pluginAction) {
    const pluginMap = {
        draw:      { slug: "neo-draw",      name: "neoDraw" },
        duplicate: { slug: "neo-duplicate", name: "neoDuplicate" },
        rename:    { slug: "neo-rename",    name: "neoRename" },
        replace:   { slug: "neo-replace",   name: "neoReplace" },
    };
    const { slug: pluginSlug, name: pluginName } = pluginMap[pluginAction] ?? {};
    if (!pluginSlug) { throw new Error("Unknown neoLibrary plugin action: " + pluginAction); }
    if (isModuleAvailable(pluginSlug)) { return true; }
    const confirmResult = await Swal.fire({
        icon: "info",
        title: neo__("Activate plugin", "Plugin aktivieren"),
        text: neo__(`The plugin ${pluginName} must be activated to use this feature.`, `Das Plugin ${pluginName} muss aktiviert werden, um diese Funktion zu nutzen.`),
        showCancelButton: true,
        confirmButtonText: neo__("Activate plugin", "Plugin aktivieren"),
        cancelButtonText: neo__("Cancel", "Abbrechen"),
    });
    if (!confirmResult.isConfirmed) { return false; }
    const settingsPageUrl = new URL(jsVar("neoLibrarySettingsPageUrl"));
    settingsPageUrl.searchParams.set("neo-settings--open-section", pluginSlug);
    reloadPage(settingsPageUrl.toString());
    return false;
}
