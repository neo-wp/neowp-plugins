import { fetchEndpoint } from "./_global--endpoint.js";
import { observeClick } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";
import { reloadPage } from "./_global-reload-page.js";
import Swal from "./_global-sweetalert2.js";

observeClick("#neo-freemius--remove-license-button", async (buttonNode) => {
    const result = await Swal.fire({
        icon: "warning",
        title: neo__("Reset license?", "Lizenz zurücksetzen?"),
        text: neo__("Are you sure you want to remove all license data? You will need to enter the license key again.", "Möchtest du wirklich alle Lizenzdaten entfernen? Der Lizenzschlüssel muss erneut eingegeben werden."),
        showCancelButton: true,
        confirmButtonText: neo__("Proceed", "Fortfahren"),
        cancelButtonText: neo__("Cancel", "Abbrechen"),
    });
    if (!result.isConfirmed) { return; }

    const buttonTextBefore = buttonNode.innerHTML;
    buttonNode.style.width = buttonNode.offsetWidth + "px";
    buttonNode.innerText = "...";
    try {
        await fetchEndpoint("/wp-json/neo/remove-freemius-license-data", { method: "POST" });
        buttonNode.innerText = neo__("Success", "Erfolg");
        buttonNode.setAttribute("success", "");
        await new Promise((resolve) => setTimeout(resolve, (1) * 1000));
        reloadPage();
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: neo__("Error while resetting", "Fehler beim Zurücksetzen"),
            text: error.message,
            showCloseButton: true,
            confirmButtonText: neo__("OK", "OK"),
        });
        buttonNode.innerHTML = buttonTextBefore;
        buttonNode.style.width = "";
    }
});
