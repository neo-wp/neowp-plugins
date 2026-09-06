import { observeOnce } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";
import { reloadPage } from "./_global-reload-page.js";
import Swal from "./_global-sweetalert2.js";
import { fetchEndpoint } from "./_global--endpoint.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { listCookies, removeCookie } from "./_global--cookie.js";

observeOnce(".neo-reset--dropdown", (dropdownNode) => {
    dropdownNode.addEventListener("change", async () => {
        const selectedOptionNode = dropdownNode.selectedOptions?.[0];
        const resetAction = dropdownNode.value;
        if (!resetAction || !selectedOptionNode) { return; }

        const resetActionConfirmTitle = selectedOptionNode.getAttribute("data-neo-reset--confirm-title");
        const resetActionConfirmText  = selectedOptionNode.getAttribute("data-neo-reset--confirm-text");
        const result = await Swal.fire({
            icon: "warning",
            title: resetActionConfirmTitle,
            text: resetActionConfirmText,
            showCancelButton: true,
            confirmButtonText: neo__("Proceed", "Fortfahren"),
            cancelButtonText: neo__("Cancel", "Abbrechen"),
        });
        if (!result.isConfirmed) { return; }

        if (resetAction === "neo-reset--delete-localstorage-and-cookies") {
            for (const cookieName of listCookies()) { if (cookieName.startsWith("neo-")) { removeCookie(cookieName); } }
            for (const key in localStorage)         { if (key.startsWith("neo-")) { localStorage.removeItem(key); } }
            dropdownNode.setAttribute("success", "");
            await new Promise((resolve) => setTimeout(resolve, (1) * 1000));
            reloadPage();
            return;
        }

        const buttonTextBefore = dropdownNode.buttonText;
        dropdownNode.disabled = true;
        dropdownNode.removeAttribute("success");
        dropdownNode.buttonText = "...";
        try {
            await fetchEndpoint("/wp-json/neo/neo-reset", { method: "POST", body: { "reset-action": resetAction } });
            dropdownNode.setAttribute("success", "");
            await new Promise((resolve) => setTimeout(resolve, (1) * 1000));
            if (resetAction === "neo-plugin-update--force-update-check") { reloadPage(jsVar("neoResetPluginPageUrl")); return; }
            reloadPage();
        } catch (error) {
            Swal.fire({
                icon: "error",
                title: neo__("Error while resetting", "Fehler beim Zurücksetzen"),
                text: error.message,
                showCloseButton: true,
                confirmButtonText: neo__("OK", "OK"),
            });
            dropdownNode.buttonText = buttonTextBefore;
            dropdownNode.removeAttribute("success");
            dropdownNode.disabled = false;
        }
    });
});
