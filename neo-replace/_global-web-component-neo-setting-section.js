import { getCookie, removeCookie, setCookie } from "./_global--cookie.js";
import { neo__ } from "./_global--translation.js";

export default class NeoSettingSection extends HTMLElement {
    static get observedAttributes() {
        return ["id", "section-title", "section-description", "icon", "icon-animated", "coming-soon", "hide-settings"];
    }

    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._isOpen = getCookie("neo-web-component-setting-section-open-neo-replace--" + this.getAttribute("id")) === "true";
        this._hasInstallOptions = false;
        this._hasSwitch = false;
        this._hasSettings = false;
        this._syncInstallOptions = this._syncInstallOptions.bind(this);
        this._syncSwitch = this._syncSwitch.bind(this);
        this._syncSettings = this._syncSettings.bind(this);
        this._hasSlotContent = this._hasSlotContent.bind(this);
        this._toggleFromHeader = this._toggleFromHeader.bind(this);
        this._toggleFromButton = this._toggleFromButton.bind(this);
        this._render();
    }

    _render() {
        this.shadowRoot.innerHTML = `
            <style>
                :host { display: block; --neo-setting-section-card-width: 800px;--neo-setting-section-space-y: 30px; --neo-setting-section-space-x: 30px; }
                .card { width: fit-content; max-width: 100%; position: relative; display: flex; flex-direction: column; gap: var(--neo-setting-section-space-y) var(--neo-setting-section-space-x); background: white; border-radius: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); padding: var(--neo-setting-section-space-y) var(--neo-setting-section-space-x); box-sizing: border-box; overflow: hidden; } /* #suppressLinterModuleNameDividedCheck */
                .header { width: var(--neo-setting-section-card-width); max-width: 100%; display: flex; align-items: center; justify-content: space-between; gap: var(--neo-setting-section-space-y) var(--neo-setting-section-space-x); cursor: default; } /* #suppressLinterModuleNameDividedCheck */
                .header.can-toggle { cursor: pointer; }
                .header:focus-visible { outline: 2px solid blue; }
                .header-main { display: flex; align-items: center; gap: 20px; }
                .header { position: relative; } .header::before { content: ""; position: absolute; top: 0; left: 0; width: calc(100% + 2 * var(--neo-setting-section-space-x)); height: calc(100% + 2 * var(--neo-setting-section-space-x)); transform: translate(calc(-1 * var(--neo-setting-section-space-x)), calc(-1 * var(--neo-setting-section-space-x))); } /* #suppressLinterModuleNameDividedCheck */
                .icon-wrap { position: relative; min-width: 75px; max-width: 75px; aspect-ratio: 1; }
                .icon-wrap img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; transition: opacity 0.25s ease; }
                .icon-animated { opacity: 0; }
                :host(.has-animated-icon) .header:hover .icon-static { opacity: 0; }
                :host(.has-animated-icon) .header:hover .icon-animated { opacity: 1; }
                .text { display: flex; flex-direction: column; gap: 12px; }
                .title-row { display: flex; align-items: center; gap: 8px; }
                .title { margin: 0; font-size: 2em; line-height: 1; font-weight: normal; hyphens: auto; }
                .title a { color: inherit; text-decoration: none; cursor: pointer; position: relative; z-index: 0; }
                .description { color: #606060; }
                .header-side { display: flex; align-items: center; gap: 20px; }
                .install-options-wrap { position: absolute; bottom: calc(-1 * var(--neo-setting-section-space-y)); right: calc(-1 * var(--neo-setting-section-space-x)); display: flex; align-items: center; } /* #suppressLinterModuleNameDividedCheck */
                .header:not(:hover) .install-options-wrap { opacity: 0.25; }
                .switch-wrap { display: flex; align-items: center; justify-content: flex-end; min-width: 60px; }
                .toggle-button { display: inline-flex; align-items: center; justify-content: center; color: #606060; cursor: pointer; background: transparent; border: none; font-size: 1em; }
                .toggle-button:focus-visible { outline: 2px solid blue; outline-offset: 4px; border-radius: 999px; }
                .header:not(.can-toggle) .toggle-button { display: none; }
                :host([hide-settings]) .toggle-button { display: none; }
                .chevron { width: 2.5em; transition: transform 200ms ease; transform-origin: center; }
                :host(.suppress-animation) .chevron, :host(.suppress-animation) .settings-container { transition: none !important; }
                .settings-container { display: flex; flex-direction: column; max-height: 0; opacity: 0; overflow: hidden; transition: max-height 200ms ease, opacity 220ms ease; will-change: max-height, opacity; }
                .settings-container { padding: 2px; margin-left: -2px; margin-right: -2px; }
                .settings-slot { display: block; min-height: 0; max-width: 100%; }
                :host(:not(.is-open)) .settings-container { display: none; }
                :host(.is-open) .chevron { transform: rotate(180deg); }
                :host(.is-open) .settings-container { opacity: 1; }
                .coming-soon-banner { background-color: #C2255C; color: white; position: absolute; bottom: 0; right: 0; font-size: 1em; font-weight: bold; padding: 0.2em 10em; transform: translate(-3em,-3em) translate(50%,50%) rotate(-45deg); transform-origin: center; }
                :host(:not([coming-soon])) .coming-soon-banner { display: none; }
                @media (max-width: 767px) {
                    :host { --neo-setting-section-space-y: 20px; --neo-setting-section-space-x: 10px; } /* #suppressLinterModuleNameDividedCheck */
                    .icon-wrap { min-width: 50px; max-width: 50px; }
                    .title { font-size: 1.4em; }
                }
            </style>
            <div class="card">
                <div class="header">
                    <div class="header-main">
                        <div class="icon-wrap">
                            <img class="icon-static" alt="">
                            <img class="icon-animated" alt="">
                        </div>
                        <div class="text">
                            <div class="title-row">
                                <h1 class="title"></h1>
                                <slot name="tooltip"></slot>
                            </div>
                            <div class="description"></div>
                        </div>
                    </div>
                    <div class="header-side">
                        <slot name="reload-button"></slot>
                        <div class="chevron-wrap">
                            <button class="toggle-button" type="button" aria-expanded="false" aria-label="${neo__("Expand settings", "Einstellungen aufklappen")}">
                                <svg class="chevron" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="switch-wrap">
                            <slot name="switch"></slot>
                        </div>
                    </div>
                    <div class="install-options-wrap" hidden>
                        <slot name="install-options"></slot>
                    </div>
                </div>
                <div class="settings-container">
                    <slot class="settings-slot" name="settings"></slot>
                </div>
                <div class="coming-soon-banner">Coming Soon</div>
            </div>
        `;
        this._header = this.shadowRoot.querySelector(".header");
        this._headerSide = this.shadowRoot.querySelector(".header-side");
        this._title = this.shadowRoot.querySelector(".title");
        this._description = this.shadowRoot.querySelector(".description");
        this._iconStatic = this.shadowRoot.querySelector(".icon-static");
        this._iconAnimated = this.shadowRoot.querySelector(".icon-animated");
        this._installOptionsWrap = this.shadowRoot.querySelector(".install-options-wrap");
        this._switchWrap = this.shadowRoot.querySelector(".switch-wrap");
        this._toggleButton = this.shadowRoot.querySelector(".toggle-button");
        this._settingsContainer = this.shadowRoot.querySelector(".settings-container");
        this._tooltipSlot = this.shadowRoot.querySelector('slot[name="tooltip"]');
        this._installOptionsSlot = this.shadowRoot.querySelector('slot[name="install-options"]');
        this._switchSlot = this.shadowRoot.querySelector('slot[name="switch"]');
        this._settingsSlot = this.shadowRoot.querySelector('slot[name="settings"]');
    }

    connectedCallback() {
        const BODY_CSS_ID = "neo-setting-section-neo-replace";
        if (!document.getElementById(BODY_CSS_ID)) {
            const globalCss = document.createElement("style");
            globalCss.id = BODY_CSS_ID;
            globalCss.textContent =`
                :where(neo-setting-section-neo-replace [slot="settings"] > *) { width: 800px; max-width: 100%;}
                :where(neo-setting-section-neo-replace [slot="settings"]) { display: flex; flex-direction: column; gap: var(--neo-setting-section-space-y) var(--neo-setting-section-space-x); } /* #suppressLinterModuleNameDividedCheck */
                :where(neo-setting-section-neo-replace [slot="settings"] > *) { padding-top: var(--neo-setting-section-space-y); border-top: 1px solid rgba(0, 0, 0, 0.08); } /* #suppressLinterModuleNameDividedCheck */
            `;
            document.head.appendChild(globalCss);
        }
        this._installOptionsSlot.addEventListener("slotchange", this._syncInstallOptions);
        this._switchSlot.addEventListener("slotchange", this._syncSwitch);
        this._settingsSlot.addEventListener("slotchange", this._syncSettings);
        this._header.addEventListener("click", this._toggleFromHeader);
        this._toggleButton.addEventListener("click", this._toggleFromButton);
        this._installOptionsWrap.addEventListener("click", event => event.stopPropagation());
        this._installOptionsWrap.addEventListener("keydown", event => event.stopPropagation());
        this._switchWrap.addEventListener("click", event => event.stopPropagation());
        this._switchWrap.addEventListener("keydown", event => event.stopPropagation());
        this._syncAttributes();
        this._syncInstallOptions();
        this._syncSwitch();
        this._syncSettings();
        this._syncOpenState({ suppressAnimation: true });
        this._syncSettingsUntilReady();
    }

    disconnectedCallback() {
        this._installOptionsSlot.removeEventListener("slotchange", this._syncInstallOptions);
        this._switchSlot.removeEventListener("slotchange", this._syncSwitch);
        this._settingsSlot.removeEventListener("slotchange", this._syncSettings);
        this._header.removeEventListener("click", this._toggleFromHeader);
        this._toggleButton.removeEventListener("click", this._toggleFromButton);
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue !== newValue) {
            this._syncAttributes();
        }
    }

    _syncAttributes() {
        const fallbackIcon = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
        const animatedIcon = this.getAttribute("icon-animated") ?? "";
        this._title.textContent = this.getAttribute("section-title") ?? "";
        if (this.getAttribute("coming-soon")) { this._title.innerHTML = `<a href="${this.getAttribute("coming-soon")}" target="_blank">${this._title.textContent}</a>` }
        this._description.textContent = this.getAttribute("section-description") ?? "";
        this._description.hidden = this._description.textContent === "";
        this._iconStatic.src = this.getAttribute("icon") || fallbackIcon;
        this._iconAnimated.src = animatedIcon || fallbackIcon;
        this.classList.toggle("has-animated-icon", animatedIcon !== "");
        this._header.classList.toggle("can-toggle", this._hasSettings && !this.hasAttribute("hide-settings"));
        if (this.hasAttribute("hide-settings") && this._isOpen) {
            this._isOpen = false;
        }
        this._syncHeaderSide();
        this._syncOpenState();
    }

    _syncInstallOptions() {
        this._hasInstallOptions = this._hasSlotContent(this._installOptionsSlot);
        this._installOptionsWrap.hidden = !this._hasInstallOptions;
        this._syncHeaderSide();
    }

    _syncSwitch() {
        this._hasSwitch = this._hasSlotContent(this._switchSlot);
        this._switchWrap.hidden = !this._hasSwitch;
        this._syncHeaderSide();
    }

    _syncSettings() {
        this._hasSettings = this._hasSlotContent(this._settingsSlot);
        this._header.classList.toggle("can-toggle", this._hasSettings && !this.hasAttribute("hide-settings"));
        if ((!this._hasSettings || this.hasAttribute("hide-settings")) && this._isOpen) {
            this._isOpen = false;
        }
        this._syncHeaderSide();
        this._syncOpenState();
    }

    _syncSettingsUntilReady() {
        if (!this.isConnected || this._hasSettings || this.hasAttribute("hide-settings")) { return; }
        this._syncSettings();
        if (this._hasSettings || !this.isConnected || this.hasAttribute("hide-settings")) { return; }
        requestAnimationFrame(() => this._syncSettingsUntilReady());
    }

    _syncHeaderSide() {
        this._headerSide.hidden = (!this._hasSettings || this.hasAttribute("hide-settings")) && !this._hasInstallOptions && !this._hasSwitch;
        this._headerSide.toggleAttribute("data-controls-empty", (!this._hasSettings || this.hasAttribute("hide-settings")) && !this._hasInstallOptions && !this._hasSwitch);
    }

    open(isOpen, { suppressAnimation = false } = {}) {
        this._syncSettings();
        if ((isOpen && (!this._hasSettings || this.hasAttribute("hide-settings"))) || this._isOpen === isOpen) { return; }
        this._isOpen = isOpen;
        this._syncOpenState({ suppressAnimation });
    }

    _toggleFromHeader() {
        this._syncSettings();
        if (!this._hasSettings || this.hasAttribute("hide-settings")) return;
        this._isOpen = !this._isOpen;
        this._syncOpenState();
    }

    _toggleFromButton(event) {
        event.stopPropagation();
        this._syncSettings();
        if (!this._hasSettings || this.hasAttribute("hide-settings")) return;
        this._isOpen = !this._isOpen;
        this._syncOpenState();
    }

    _syncOpenState({ suppressAnimation = false } = {}) {
        if (suppressAnimation) { this.classList.add("suppress-animation"); }
        this.classList.toggle("is-open", this._isOpen && this._hasSettings && !this.hasAttribute("hide-settings"));
        if (this._isOpen) { setCookie("neo-web-component-setting-section-open-neo-replace--" + this.getAttribute("id"), "true", (5) * 60); } else { removeCookie("neo-web-component-setting-section-open-neo-replace--" + this.getAttribute("id")); }
        this._settingsContainer.style.maxHeight = this._isOpen && this._hasSettings ? this._settingsContainer.scrollHeight + "px" : "0px";
        this._header.setAttribute("aria-expanded", String(this._isOpen));
        this._toggleButton.setAttribute("aria-expanded", String(this._isOpen));
        this._toggleButton.setAttribute("aria-label", this._isOpen ? neo__("Collapse settings", "Einstellungen zuklappen") : neo__("Expand settings", "Einstellungen aufklappen"));
        if (suppressAnimation) { requestAnimationFrame(() => this.classList.remove("suppress-animation")); }

        if (this._isOpen && this._hasSettings) {
            const onTransitionEnd = () => {
                this._settingsContainer.style.maxHeight = "none";
                this._settingsContainer.removeEventListener("transitionend", onTransitionEnd);
            };
            if (this.classList.contains("suppress-animation")) { onTransitionEnd(); } else { this._settingsContainer.addEventListener("transitionend", onTransitionEnd); }
        }
    }

    _hasSlotContent(slot) {
        return slot.assignedNodes({ flatten: true }).some(node => node.nodeType === Node.ELEMENT_NODE ? node.childNodes.length > 0 && (node.querySelector("*") !== null || node.textContent.trim() !== "") : node.textContent.trim() !== "");
    }
}
