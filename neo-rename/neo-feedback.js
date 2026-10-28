import { escapeHtml } from "./_global--dom-node-helper.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import Swal from "./_global-sweetalert2.js";
import { neoError } from "./_global--log.js";

let cssLoadingPromise = null;

export async function interfaceOpenFeedbackDialog20260802({ swalContainerClass = "" } = {}) {
    if (!cssLoadingPromise) {
        cssLoadingPromise = new Promise((resolve, reject) => {
            const linkNode = document.createElement("link");
            linkNode.rel = "stylesheet"; linkNode.href = pluginUrl() + "/neo-feedback.css"; linkNode.setAttribute("data-neo-feedback--css", "true");
            linkNode.addEventListener("load", resolve, { once: true });
            linkNode.addEventListener("error", () => { cssLoadingPromise = null; linkNode.remove(); reject(new Error("Feedback stylesheet could not be loaded")); }, { once: true });
            document.head.appendChild(linkNode);
        });
    }
    await cssLoadingPromise;
    const customContainerClass = ["neo-feedback--swal", swalContainerClass].filter(Boolean).join(" ");
    const result = await Swal.fire({
        title: neo__("Send feedback", "Feedback senden"),
        html: `<div class="neo-feedback--form">
            <label for="neo-feedback--text">${neo__("What would you like to tell us?", "Was möchtest du uns mitteilen?")}</label>
            <textarea id="neo-feedback--text" placeholder="${neo__("Ideas, wishes, problems, or anything else...", "Ideen, Wünsche, Probleme oder etwas ganz anderes...")}"></textarea>
            <div class="neo-feedback--optional-field" data-neo-feedback--field="email">
                <div class="neo-feedback--field-heading"><label for="neo-feedback--email">${neo__("Email address", "E-Mail-Adresse")}</label><button type="button" class="neo-feedback--hide-field">${neo__("Don't include", "Nicht mitsenden")}</button></div>
                <input id="neo-feedback--email" type="text" value="${escapeHtml(jsVar("neoFeedbackEmail") ?? "")}">
            </div>
            <div class="neo-feedback--optional-field" data-neo-feedback--field="domain">
                <div class="neo-feedback--field-heading"><label for="neo-feedback--domain">${neo__("Website domain", "Website-Domain")}</label><button type="button" class="neo-feedback--hide-field">${neo__("Don't include", "Nicht mitsenden")}</button></div>
                <input id="neo-feedback--domain" type="text" value="${escapeHtml(jsVar("neoFeedbackDomain") ?? "")}" disabled>
            </div>
            <p class="neo-feedback--privacy-note">${neo__("To help us understand and answer your feedback, the optional email address and domain as well as plugin, WordPress, PHP, language, and browser information are sent to neoWP and stored in a protected server log. You can hide the email address and domain above.", "Damit wir dein Feedback gut verstehen und beantworten können, werden die optionale E-Mail-Adresse und Domain sowie Plugin-, WordPress-, PHP-, Sprach- und Browserinformationen an neoWP gesendet und in einem geschützten Server-Log gespeichert. E-Mail-Adresse und Domain kannst du oben verbergen.")}</p>
        </div>`,
        showCancelButton: true, showLoaderOnConfirm: true, focusConfirm: false,
        confirmButtonText: neo__("Send feedback", "Feedback senden"), cancelButtonText: neo__("Cancel", "Abbrechen"),
        customClass: { container: customContainerClass },
        allowOutsideClick: () => !Swal.isLoading(),
        didOpen: (popupNode) => {
            popupNode.querySelector("#neo-feedback--text").focus();
            for (const hideButtonNode of popupNode.querySelectorAll(".neo-feedback--hide-field")) {
                const inputNode = hideButtonNode.closest(".neo-feedback--optional-field").querySelector("input"); let includedValue = inputNode.value;
                hideButtonNode.addEventListener("click", () => {
                    if (!inputNode.hidden) { includedValue = inputNode.value; inputNode.value = ""; inputNode.hidden = true; hideButtonNode.textContent = neo__("Include", "Mit senden"); return; }
                    inputNode.value = includedValue; inputNode.hidden = false; hideButtonNode.textContent = neo__("Don't include", "Nicht mitsenden");
                });
            }
        },
        preConfirm: async () => {
            const feedback = document.querySelector("#neo-feedback--text").value;
            if (feedback.trim() === "") { Swal.showValidationMessage(neo__("Please enter your feedback.", "Bitte gib dein Feedback ein.")); return false; }
            const payload = new URLSearchParams({
                feedback,
                email: document.querySelector("#neo-feedback--email")?.value ?? "",
                domain: document.querySelector("#neo-feedback--domain")?.value ?? "",
                plugin_slug: jsVar("neoFeedbackPluginSlug"),
                plugin_edition: jsVar("neoFeedbackPluginEdition"),
                plugin_version: jsVar("neoFeedbackPluginVersion"),
                wordpress_version: jsVar("neoFeedbackWordPressVersion"),
                php_version: jsVar("neoFeedbackPhpVersion"),
                locale: jsVar("neoFeedbackLocale"),
                user_agent: navigator.userAgent,
            });
            const requestStartedAt = performance.now(); let responseStatus = null;
            try {
                const response = await fetch(jsVar("neoFeedbackEndpointUrl"), { method: "POST", body: payload });
                responseStatus = response.status;
                const responseData = await response.json().catch(() => null);
                if (!response.ok || !responseData?.success) { const requestError = new Error(responseData?.message || neo__("The feedback could not be sent.", "Das Feedback konnte nicht gesendet werden.")); requestError.name = response.ok ? "ResponseError" : "HttpError"; throw requestError; }
                return true;
            } catch (error) { neoError("neoFeedback request failed:", { statusCode: responseStatus, durationMs: Math.round(performance.now() - requestStartedAt), errorClass: error?.name || "Error" }); Swal.showValidationMessage(escapeHtml(error.message || neo__("The feedback could not be sent. Please try again.", "Das Feedback konnte nicht gesendet werden. Bitte versuche es erneut."))); return false; }
        },
    });
    if (!result.isConfirmed) { return false; }
    await Swal.fire({ icon: "success", title: neo__("Thank you for your feedback!", "Danke für dein Feedback!"), text: neo__("Your message has reached the neoWP team.", "Deine Nachricht ist beim neoWP-Team angekommen."), confirmButtonText: neo__("Done", "Fertig"), customClass: { container: customContainerClass } });
    return true;
}
