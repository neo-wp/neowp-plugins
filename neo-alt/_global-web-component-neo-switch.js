import { pricingUrl } from "./_global-pricing-url.js";

export default class NeoSwitch extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.shadowRoot.innerHTML = `
            <style>
                :host { display: flex; width: 100%; }
                :host([disabled]) .switch-label .switch-slider:not(.more-specificity) { background-color: #d0d0d0; }
                :host([disabled]) .switch-label input:checked + .switch-slider:not(.loading) { background-color: #c0e0c0; }
                .outer-container { position: relative; display: flex; justify-content: space-between; align-items: center; gap: 10px; width: 100%; }
                .pro-link-overlay { position: absolute; inset: 0; z-index: 10; text-decoration: none; background: transparent; cursor: pointer; }
                .label-text { display: flex; align-items: center; gap: 6px; font-size: 1em; color: #000; }
                .switch-label { position: relative; display: inline-block; width: 60px; min-width: 60px; height: 34px; cursor: default; }
                :host(:not([disabled])) .switch-label { cursor: pointer; }
                .switch-label input { opacity: 0; width: 0; height: 0; }
                .switch-slider { position: absolute; inset: 0; background-color: #D000D0; transition: background-color 0.2s; border-radius: 999px; }
                .switch-circle { position: absolute; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: left 0.2s; border-radius: 50%; display: grid; place-items: center; overflow: hidden; }
                .switch-circle .pro-crown { width: 16px; height: 16px; display: block; }
                .switch-label input:checked + .switch-slider:not(.loading) { background-color: #00D000; }
                .switch-label input:checked + .switch-slider:not(.loading) .switch-circle { left: calc(100% - 26px - 4px); }
                .switch-label input:checked + .switch-slider.loading .switch-circle { left: calc(100% - 26px - 4px); }
                .switch-label input:focus + .switch-slider { box-shadow: 0 0 1px #00D000; }
                .switch-slider.loading { background-color: #FFC003; }
                .switch-slider.loading .switch-circle {animation: switch-loading 1s infinite linear; border: 8px solid #606060; border-top-color: transparent; border-bottom-color: transparent; box-sizing: border-box; }
                .switch-slider.loading .switch-circle .pro-crown { display: none; }
                @keyframes switch-loading { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>

            <div class="outer-container">
                <span class="label-text"></span>
                <label class="switch-label" role="switch" tabindex="0" aria-checked="false" aria-disabled="false">
                    <input type="checkbox" name="neo-global--switch-input" aria-label="Toggle switch" tabindex="-1" />
                    <span class="switch-slider">
                        <span class="switch-circle"></span>
                    </span>
                </label>
            </div>
        `;
        this._loadingStart = 0;
        this._loadingTimeout = null;
        this._isLoadingVisible = false;
        this._proDisabledTitle = null;
        this._proDisabledTitleInFlight = false;
        const labelElem = this.shadowRoot.querySelector(".switch-label");
        labelElem.addEventListener("click", (e) => {
            if (this.loading || this.disabled) {
                e.preventDefault();
                return;
            }
            e.preventDefault();
            this._handleToggle();
        });
        labelElem.addEventListener("keydown", (e) => {
            if ((e.key === " " || e.key === "Enter") && !this.loading && !this.disabled) {
                e.preventDefault();
                this._handleToggle();
            }
        });
    }

    static get observedAttributes() {
        return ["loading", "checked", "label", "disabled", "disabled-title", "only-enabled-if-pro"];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "loading") {
            this._syncLoadingVisibility();
            this._updateAccessibility();
        }
        if (name === "checked") {
            const isChecked = newValue !== null;
            this.shadowRoot.querySelector("input").checked = isChecked;
            this._updateAccessibility();
        }
        if (name === "label") {
            this._updateLabel();
        }
        if (name === "disabled") {
            this._updateAccessibility();
            this._updateDisabledTitle();
        }
        if (name === "disabled-title") {
            this._updateDisabledTitle();
        }
        if (name === "only-enabled-if-pro") {
            this._applyProState();
        }
    }

    connectedCallback() {
        this._syncLoadingVisibility();
        this._updateAccessibility();
        this._updateLabel();
        this._updateDisabledTitle();
        this.shadowRoot.querySelector("input").checked = this.hasAttribute("checked");
        this._applyProState();
    }

    disconnectedCallback() {
        if (this._loadingTimeout) {
            clearTimeout(this._loadingTimeout);
            this._loadingTimeout = null;
        }
    }

    get checked() { return this.shadowRoot.querySelector("input").checked; }
    set checked(val) { 
        this.shadowRoot.querySelector("input").checked = val;
        if (val) { this.setAttribute("checked", ""); } else { this.removeAttribute("checked"); }
        this._updateAccessibility();
    }

    get disabled() { return this.hasAttribute("disabled"); }
    set disabled(val) { val ? this.setAttribute("disabled", "true") : this.removeAttribute("disabled"); }

    get loading() { return this.hasAttribute("loading"); }
    set loading(val) { val ? this.setAttribute("loading", "true") : this.removeAttribute("loading"); }

    toggle() { return this._handleToggle(); }

    _syncLoadingVisibility() {
        if (this.loading) {
            this._loadingStart = Date.now();
            this._isLoadingVisible = true;
            if (this._loadingTimeout) {
                clearTimeout(this._loadingTimeout);
                this._loadingTimeout = null;
            }
            this._updateLoading();
            return;
        }
        const elapsed = Date.now() - (this._loadingStart || 0);
        const minDuration = 500;
        if (this._loadingTimeout) {
            clearTimeout(this._loadingTimeout);
            this._loadingTimeout = null;
        }
        if (this._isLoadingVisible && elapsed < minDuration) {
            this._loadingTimeout = setTimeout(() => {
                this._isLoadingVisible = false;
                this._loadingTimeout = null;
                this._updateLoading();
            }, minDuration - elapsed);
            return;
        }
        this._isLoadingVisible = false;
        this._updateLoading();
    }

    _updateLoading() {
        const slider = this.shadowRoot.querySelector(".switch-slider");
        if (this._isLoadingVisible) slider.classList.add("loading");
        else slider.classList.remove("loading");
    }

    _updateAccessibility() {
        const labelElem = this.shadowRoot.querySelector(".switch-label");
        labelElem.setAttribute("aria-checked", this.checked ? "true" : "false");
        labelElem.setAttribute("aria-disabled", (this.loading || this.disabled) ? "true" : "false");
    }

    _updateLabel() {
        const labelTextElem = this.shadowRoot.querySelector(".label-text");
        if (this.hasAttribute("label") && this.getAttribute("label").trim() !== "") {
            labelTextElem.textContent = this.getAttribute("label");
            labelTextElem.style.display = "flex";
        } else {
            labelTextElem.textContent = "";
            labelTextElem.style.display = "none";
        }
    }

    _updateDisabledTitle() {
        if (!this.disabled) {
            if (this.hasAttribute("data-title-original")) {
                this.setAttribute("title", this.getAttribute("data-title-original"));
                this.removeAttribute("data-title-original");
            }
            return;
        }
        if (this.hasAttribute("data-pro-disabled")) {
            if (!this.hasAttribute("data-title-original")) { this.setAttribute("data-title-original", this.getAttribute("title") || ""); }
            if (this._proDisabledTitle) {
                this.setAttribute("title", this._proDisabledTitle);
            } else if (!this._proDisabledTitleInFlight) {
                this._proDisabledTitleInFlight = true;
                (async () => {
                    const { neo__ } = await import("./_global--translation.js");
                    this._proDisabledTitle = neo__("Get neoPro now to unlock this feature", "Jetzt neoPro holen, um dieses Feature freizuschalten");
                    this.setAttribute("title", this._proDisabledTitle);
                    this._proDisabledTitleInFlight = false;
                })().catch(() => {
                    this.setAttribute("title", "Get neoPro");
                    this._proDisabledTitleInFlight = false;
                });
            }
            return;
        }
        if (this.hasAttribute("disabled-title")) {
            if (!this.hasAttribute("data-title-original")) { this.setAttribute("data-title-original", this.getAttribute("title") || ""); }
            this.setAttribute("title", this.getAttribute("disabled-title"));
        }
    }

    _handleToggle() {
        return (async () => {
            if (this.disabled || this.loading) return;
            const previousState = this.checked;
            this.checked = !previousState;
            let callbackResult;
            try {
                if (typeof this.onchange === "function") {
                    callbackResult = this.onchange({ checked: this.checked });
                    if (callbackResult && typeof callbackResult.then === "function") {
                        this.loading = true;
                        await callbackResult;
                    }
                }
            } catch (error) {
                this.checked = previousState;
                throw error;
            } finally {
                this.loading = false;
            }
        })();
    }

    _applyProState() {
        const hasProAttr = this.hasAttribute("only-enabled-if-pro");
        const outerContainer = this.shadowRoot.querySelector(".outer-container");
        const circleElem = outerContainer.querySelector(".switch-circle");

        let crownElem = circleElem.querySelector(".pro-crown");
        if (hasProAttr) {
            if (!crownElem) {
                crownElem = document.createElement("neo-pro-crown-neo-alt");
                crownElem.classList.add("pro-crown");
                circleElem.appendChild(crownElem);
            }

            this._setProDisabled(true);
            this._toggleProPricingLink(true);
        } else {
            if (crownElem) crownElem.remove();
            this._setProDisabled(false);
            this._toggleProPricingLink(false);
        }
    }

    _toggleProPricingLink(show) {
        const outer = this.shadowRoot.querySelector(".outer-container");
        let link = outer.querySelector(".pro-link-overlay");
        if (show) {
            const href = pricingUrl();
            if (!link) {
                link = document.createElement("a");
                link.className = "pro-link-overlay";
                link.target = "_blank";
                link.rel = "noopener";
                link.setAttribute("aria-label", "Open pricing");

                outer.appendChild(link);
            }
            link.href = href;
        } else if (link) {
            link.remove();
        }
    }

    _setProDisabled(shouldDisable) {
        if (shouldDisable) {
            if (!this.hasAttribute("disabled")) {
                this.setAttribute("disabled", "true");
                this.setAttribute("data-pro-disabled", "true");
                this._updateAccessibility();
                this._updateDisabledTitle();
            } else if (!this.hasAttribute("data-pro-disabled")) {

            }
        } else {
            if (this.hasAttribute("data-pro-disabled")) {
                this.removeAttribute("data-pro-disabled");
                this.removeAttribute("disabled");
                this._updateAccessibility();
                this._updateDisabledTitle();
            }
        }
    }
}
