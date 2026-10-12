import { neo__ } from "./_global--translation.js";
import { neoError } from "./_global--log.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { reloadPage } from "./_global-reload-page.js";
import Swal from "./_global-sweetalert2.js";
import { jsVar } from "./_global--enqueue-loader.js";

// Enable downloading of Pro content as a ZIP file when the user explicitly requests it via a button
const result = await Swal.fire({
    title: neo__("Install neoMedia Pro", "neoMedia Pro installieren"),
    html:
        `<p style="text-align: left;">
            ${neo__(`Thanks for entering your neoMedia Pro license key! To enjoy all Pro features, please install the Pro version of the plugin.`,
                    `Vielen Dank, dass du deinen neoMedia-Pro-Lizenzschlüssel eingegeben hast! Damit du alle Pro-Funktionen nutzen kannst, installiere bitte die Pro-Version des Plugins.`)}
        </p>`
    ,
    icon: "info",
    showCancelButton: true, showDenyButton: true, showConfirmButton: jsVar("neoProInstallerIsProInstallerAllowed"),
    cancelButtonText: neo__("Cancel", "Abbrechen"), denyButtonText: neo__("Download ZIP", "ZIP herunterladen"), confirmButtonText: neo__("Install Now", "Jetzt installieren"),
    cancelButtonColor:  "#DC3644",
    denyButtonColor:    "#3085D6",
    confirmButtonColor: "#28A744",
    allowOutsideClick: false,
    allowEscapeKey: true,
    showLoaderOnConfirm: true,
    preConfirm: async () => {
        try {
            const response = await fetchEndpoint("/wp-json/neo/install-neo-media-pro-neo-duplicate", { method: "POST" });
            const respText = await response.text();
            if (respText.includes("Download failed")) {
                neoError("Error downloading plugin", respText);
                throw new Error("Error downloading plugin - " + respText);
            }

            reloadPage(jsVar("neoProInstallerPluginsUrl"));
        } catch (error) {
            alert(neo__("There was an error while installing neoMedia Pro: ", "Es gab einen Fehler beim Installieren von neoMedia Pro: ") + error.message);
            throw error;
        }
    },
});
if (result.isDenied) {
    window.open(jsVar("neoProInstallerNeoMediaProZipUrl"), "_blank");
    if (jsVar("neoProInstallerIsProInstallerAllowed")) {
        reloadPage();
    } else {
        reloadPage(jsVar("neoProInstallerPluginsUrl"));
    }
} else if (result.dismiss === Swal.DismissReason.cancel) {
    try {
        await fetchEndpoint("/wp-json/neo/neo-pro-installer--remove-license-key", { method: "POST" });
        reloadPage(jsVar("neoProInstallerSettingsPageUrl"));
    } catch (error) {
        alert(neo__("There was an error while removing the license key: ", "Es gab einen Fehler beim Entfernen des Lizenzschlüssels: ") + error.message);
        throw error;
    }
}
