import { observeOnce } from "./_global--observer.js";

export async function interfaceForceHideSettings20260327(settingsId, forceHideSettings) {
    const settingsSectionNode = await observeOnce(`#neo-settings--section-${settingsId}`);
    settingsSectionNode.toggleAttribute("hide-settings", forceHideSettings);
}

export async function interfaceOpenAllSettingsSections20260404() {
    for (const sectionNode of document.querySelectorAll("neo-setting-section-neo-alt")) {
        while (!sectionNode.open) { await new Promise(requestAnimationFrame); }
        sectionNode.open(true);
    }
}
