import { DomNodeHelper, escapeHtml } from "./_global--dom-node-helper.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { setAiGenerationState } from "./_global--ai-generation-state.js";
import { observeResize } from "./_global--observer.js";
import Swal from "./_global-sweetalert2.js";

export function integrateWpAltTextField({ inputNode, getImageUrl, getImageTitle = () => null, getSetAltText = () => null, keepInputInPlace = false }) {
    if (!(inputNode && !inputNode.closest(".neo-alt--wp-alt-text-integration"))) { return; }
    const integrationNode = new DomNodeHelper(`<span class="neo-alt--wp-alt-text-integration"><span class="neo-alt--wp-alt-text-field"><span class="neo-alt--wp-alt-text-effect-field"></span><neo-info-tooltip-neo-alt class="neo-alt--wp-alt-text-ai-tooltip" no-click-open instant-hover><button slot="icon" type="button" class="neo-alt--wp-alt-text-ai-button" aria-label="${escapeHtml(neo__("Generate with AI using neoAlt", "Mit AI über neoAlt generieren"))}"><img src="${escapeHtml(pluginUrl())}/_global-lucide-icons-thirdparty/sparkles.svg" alt=""></button>${escapeHtml(neo__("Generate with AI using neoAlt", "Mit AI über neoAlt generieren"))}</neo-info-tooltip-neo-alt></span><a class="neo-alt--wp-overview-link" href="${escapeHtml(jsVar("neoAltOverviewUrl"))}" target="_blank" rel="noopener noreferrer">${escapeHtml(neo__("Bulk edit alt texts with neoAlt", "Alt-Texte gesammelt mit neoAlt bearbeiten"))}</a></span>`).getNode();
    const fieldNode = integrationNode.querySelector(".neo-alt--wp-alt-text-field"); const effectFieldNode = integrationNode.querySelector(".neo-alt--wp-alt-text-effect-field"); const tooltipNode = integrationNode.querySelector(".neo-alt--wp-alt-text-ai-tooltip"); const buttonNode = integrationNode.querySelector(".neo-alt--wp-alt-text-ai-button");
    if (keepInputInPlace) { integrationNode.classList.add("neo-alt--wp-alt-text-integration-sibling"); inputNode.insertAdjacentElement("afterend", integrationNode); } else { inputNode.parentNode.insertBefore(integrationNode, inputNode); fieldNode.insertBefore(inputNode, fieldNode.firstChild); }
    observeResize(inputNode, () => {
        const inputRect = inputNode.getBoundingClientRect(); const fieldRect = fieldNode.getBoundingClientRect();
        effectFieldNode.style.left = inputRect.left - fieldRect.left + "px"; effectFieldNode.style.width = inputRect.width + "px"; effectFieldNode.style.top = inputRect.top - fieldRect.top + "px"; effectFieldNode.style.height = inputRect.height + "px"; tooltipNode.style.top = inputRect.top - fieldRect.top + inputRect.height / 2 + "px";
    });
    let programmaticInput = false;
    inputNode.addEventListener("input", async () => {
        if (programmaticInput) { return; }
        const imageUrl = getImageUrl() || "";
        if (imageUrl === "") { return; }
        await (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceClearGeneratedImageTexts20260713"))({ imageUrl, textType: "alt" });
    });
    buttonNode.addEventListener("click", async () => {
        const imageUrl = getImageUrl() || "";
        if (imageUrl === "") { await Swal.fire({ customClass: { container: "neo-alt--wp-integration-swal" }, icon: "error", title: neo__("Generation failed", "Generierung fehlgeschlagen"), text: neo__("The image URL could not be determined.", "Die Bild-URL konnte nicht ermittelt werden.") }); return; }
        const setAltText = getSetAltText(); const inputWasDisabled = inputNode.disabled;
        try {
            setAiGenerationState({ fieldNode: effectFieldNode, buttonNode, generating: true }); buttonNode.disabled = true; inputNode.disabled = true;
            const generatedAltText = await (await neoLoadInterfaceFunc("neo-alt", "neo-ai--image-text-generation.js", "interfaceGenerateImageText20260713"))({ imageUrl, textType: "alt", imageTitle: getImageTitle(), imageAltText: inputNode.value, swalContainerClass: "neo-alt--wp-integration-swal" });
            if (generatedAltText === null) { return; }
            programmaticInput = true;
            if (setAltText) { setAltText(generatedAltText); } else { inputNode.value = generatedAltText; inputNode.dispatchEvent(new Event("input", { bubbles: true })); inputNode.dispatchEvent(new Event("change", { bubbles: true })); }
            programmaticInput = false;
        } catch (error) { await Swal.fire({ customClass: { container: "neo-alt--wp-integration-swal" }, icon: "error", title: neo__("Generation failed", "Generierung fehlgeschlagen"), text: error?.message || neo__("Could not generate alt text.", "Alt-Text konnte nicht generiert werden.") }); }
        finally { programmaticInput = false; setAiGenerationState({ fieldNode: effectFieldNode, buttonNode, generating: false }); inputNode.disabled = inputWasDisabled; buttonNode.disabled = inputNode.disabled || inputNode.readOnly; }
    });
    buttonNode.disabled = inputNode.disabled || inputNode.readOnly;
}
