import { addEventListenerWithInitialCall, observeClick, observeOnce } from "./_global--observer.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";

(await neoLoadInterfaceFunc("neo-animate", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-settings--section-group-toggle", "right", "new-plugins");

window.neoSettingsMenuLocationNoticeDispatchChange = ({ checked, isSaved }) => {
    window.dispatchEvent(new CustomEvent("neo-settings--menu-location-notice-change", { detail: { checked, isSaved } }));
};

window.addEventListener("neo-settings--menu-location-notice-change", (event) => {
    const descriptionNode = document.querySelector("#neo-settings--menu-location-description");
    if (!descriptionNode) { return; }
    document.querySelector("#neo-settings--menu-location-notice")?.remove();
    if (!event.detail.checked) { return; }
    const noticeNode = document.createElement("span");
    noticeNode.id = "neo-settings--menu-location-notice";
    noticeNode.style.display = "block";
    noticeNode.style.color = "#166534";
    noticeNode.style.fontWeight = "600";
    noticeNode.textContent = descriptionNode.getAttribute("data-neo-settings--menu-location-notice");
    descriptionNode.append(noticeNode);
});

function openSectionGroup(groupNode, toggleNode, { suppressAnimation = false } = {}) {
    groupNode.hidden = false;
    groupNode.classList.remove("neo-settings--section-group-collapsed"); toggleNode.classList.add("neo-settings--section-group-toggle-open");
    if (suppressAnimation) { groupNode.style.maxHeight = "none"; groupNode.style.overflow = "visible"; groupNode.style.opacity = "1"; return; }
    groupNode.style.maxHeight = "0px"; groupNode.style.overflow = "hidden"; requestAnimationFrame(() => { groupNode.style.maxHeight = groupNode.scrollHeight + "px"; groupNode.style.opacity = "1"; });
}

function closeSectionGroup(groupNode, toggleNode) {
    groupNode.classList.add("neo-settings--section-group-collapsed"); toggleNode.classList.remove("neo-settings--section-group-toggle-open");
    groupNode.style.maxHeight = groupNode.scrollHeight + "px"; groupNode.style.overflow = "hidden"; requestAnimationFrame(() => { groupNode.style.maxHeight = "0px"; groupNode.style.opacity = "0"; });
}

observeOnce(".neo-settings--section-group-toggle", async (toggleNode) => {
    let groupNode; while (!groupNode) { groupNode = toggleNode.parentNode.nextElementSibling; await new Promise(requestAnimationFrame); }
    observeClick(toggleNode, (toggleNode) => {
        if (groupNode.classList.contains("neo-settings--section-group-collapsed")) {
            openSectionGroup(groupNode, toggleNode);
        } else {
            closeSectionGroup(groupNode, toggleNode);
        }
    });
    groupNode.addEventListener("transitionend", (event) => {
        if (event.propertyName !== "max-height") { return; }
        if (!groupNode.classList.contains("neo-settings--section-group-collapsed")) { groupNode.style.maxHeight = "none"; groupNode.style.overflow = "visible"; return; }
        groupNode.hidden = true;
    });
});

async function openSettingsSection(settingsId, { suppressAnimation = true } = {}) {
    const sectionNode = await observeOnce(`#neo-settings--section-${settingsId}`);
    if (!sectionNode) { return null; }
    const groupNode = sectionNode.closest(".neo-settings--section-group");
    if (groupNode?.classList.contains("neo-settings--section-group-collapsed")) { openSectionGroup(groupNode, groupNode.previousElementSibling.querySelector(".neo-settings--section-group-toggle"), { suppressAnimation }); }
    while (!sectionNode.open) { await new Promise(requestAnimationFrame); }
    while (!sectionNode.shadowRoot?.querySelector(".header.can-toggle")) { await new Promise(requestAnimationFrame); }
    sectionNode.open(true, { suppressAnimation });
    await new Promise(requestAnimationFrame);
    sectionNode.scrollIntoView({ behavior: suppressAnimation ? "auto" : "smooth", block: "start" });
    return sectionNode;
}

export async function interfaceOpenSettingsSection20260603(settingsId, { suppressAnimation = true } = {}) { return openSettingsSection(settingsId, { suppressAnimation }); }

addEventListenerWithInitialCall(window, "hashchange", async () => {
    const openSectionParam = new URLSearchParams(location.search).get("neo-settings--open-section");
    if (!openSectionParam && !location.hash) { return; }
    const sectionSelector = openSectionParam ? "#neo-settings--section-" + openSectionParam : location.hash;
    const hashIsSection = sectionSelector.startsWith("#neo-settings--section-");
    let sectionNode;
    if (openSectionParam) { sectionNode = await openSettingsSection(openSectionParam, { suppressAnimation: true }); }
    else if (hashIsSection) { sectionNode = await openSettingsSection(sectionSelector.replace("#neo-settings--section-", ""), { suppressAnimation: false }); }
    else { sectionNode = await observeOnce(sectionSelector); }
    if (!sectionNode) { return; }
    if (openSectionParam) { const cleanedUrl = new URL(location.href); cleanedUrl.searchParams.delete("neo-settings--open-section"); cleanedUrl.hash = sectionSelector + "-scrolled"; history.replaceState(null, "", cleanedUrl.toString()); return; }
    if (!hashIsSection) { sectionNode.scrollIntoView({ behavior: "smooth", block: "start" }); history.replaceState(null, "", location.hash + "-scrolled"); return; }
    history.replaceState(null, "", location.hash + "-scrolled");
});
