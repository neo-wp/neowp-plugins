import { neo__ } from "./_global--translation.js";

export default class NeoDropdownButton extends HTMLElement {
    static get observedAttributes() { return ["button-text", "disabled", "success", "only-round-corner-top-left"]; }

    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._syncSelectOptions    = this._syncSelectOptions.bind(this);
        this._syncPendingSelection = this._syncPendingSelection.bind(this);
        this._onButtonClick        = this._onButtonClick.bind(this);
        this._pendingValue = null;
        this.shadowRoot.innerHTML = `
            <style>
                :host                                       { display: flex; align-items: stretch; font-size: 14px; }
                neo-select-neo-duplicate, button { font-size: 1em; }
                neo-select-neo-duplicate         { --neo-select-border-color-neo-duplicate: #C0C0C0; --neo-select-border-radius-neo-duplicate: 10px 0 0 10px; --neo-select-padding-neo-duplicate: 0.8em 1.9em 0.8em 0.8em; }
                button                                      { border: 1px solid #C0C0C0; }
                button                                      { border-radius: 0 10px 10px 0; padding: 0.8em 1.0em 0.8em 1.0em; }
                :host([only-round-corner-top-left]) neo-select-neo-duplicate { --neo-select-border-radius-neo-duplicate: 10px 0 0 0; }
                :host([only-round-corner-top-left]) button  { border-radius: 0; }
                button                                      { margin-left: -1px; }
                neo-select-neo-duplicate         { max-width: calc(100vw - 140px); }
                button                                      { background-color: var(--wp-admin-theme-color, #2271b1); color: white; cursor: pointer; }
                button:not(:disabled):hover                 { filter: brightness(110%); }
                button:not(:disabled)                       { border-color: var(--wp-admin-theme-color, #2271b1); }
                button:disabled                             { color: #a7aaad; background-color: #f6f7f7; cursor: default; }
                button .button-text-success                 { display: none; }
                :host([success]) button                     { background-color: #00D000; color: white; }
                :host([success]) button                     { .button-text { display: none; } .button-text-success { display: inline; } }
            </style>
            <neo-select-neo-duplicate></neo-select-neo-duplicate>
            <slot style="display:none;"></slot>
            <button><span class="button-text">OK</span><span class="button-text-success">${neo__("Done", "Fertig")}</span></button>
        `;
        this._select = this.shadowRoot.querySelector("neo-select-neo-duplicate");
        this._button = this.shadowRoot.querySelector("button");
        this._slot   = this.shadowRoot.querySelector("slot");
    }

    connectedCallback() {
        (async () => {
            await customElements.whenDefined("neo-select-neo-duplicate");
            this._slot.addEventListener("slotchange", this._syncSelectOptions);
            this._select.addEventListener("change", this._syncPendingSelection);
            this._button.addEventListener("click", this._onButtonClick);
            this._syncSelectOptions();
            if (this._pendingValue !== null) { this._applyValue(this._pendingValue); }
            this._syncButtonText();
            this._syncDisabledState();
        })();
    }

    disconnectedCallback() {
        this._slot.removeEventListener("slotchange", this._syncSelectOptions);
        this._select.removeEventListener("change", this._syncPendingSelection);
        this._button.removeEventListener("click", this._onButtonClick);
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "button-text") { this._syncButtonText(); }
        if (name === "disabled") { this._syncDisabledState(); }
    }

    get disabled()        { return this.hasAttribute("disabled"); }          set disabled(isDisabled) { this.toggleAttribute("disabled", isDisabled); }
    get buttonText()      { return this.getAttribute("button-text") ?? ""; } set buttonText(buttonText) { this.setAttribute("button-text", buttonText ?? ""); }
    get value()           { return this._select?.value ?? this._pendingValue ?? ""; } set value(newValue) { this._pendingValue = newValue ?? ""; if (customElements.get("neo-select-neo-duplicate")) { this._applyValue(this._pendingValue); } }
    get selectedOptions() { return this._select?.selectedOptions ?? []; }

    _applyValue(newValue) {
        this._select.value = newValue ?? "";
        this._syncLightDomSelection();
        this._syncDisabledState();
    }

    _syncPendingSelection(event) {
        event.stopPropagation();
        this._syncLightDomSelection();
        this._syncDisabledState();
    }

    _onButtonClick(event) {
        event.stopPropagation();
        if (this._button.disabled) { return; }
        this.dispatchEvent(new Event("change", { bubbles: true }));
    }

    _syncSelectOptions() {
        this._select.replaceChildren(...this._slot.assignedElements({ flatten: true }).map(node => node.cloneNode(true)));
        if ([...this._select.options].some(optionNode => optionNode.value === this._select.value)) { this._select.value = this._select.value; } else { this._select.value = ""; }
        this._syncLightDomSelection();
        this._syncDisabledState();
    }

    _syncLightDomSelection() {
        const updateOptionSelected = (node) => {
            if (node.tagName === "OPTGROUP") { [...node.querySelectorAll("option")].forEach(optionNode => updateOptionSelected(optionNode)); }
            if (node.tagName === "OPTION") { node.toggleAttribute("selected", node.value === this._select.value); }
        };
        this._slot.assignedElements({ flatten: true }).forEach(node => updateOptionSelected(node));
    }

    _syncButtonText() {
        this._button.querySelector(".button-text").textContent = this.buttonText;
    }

    _syncDisabledState() {
        this._select.toggleAttribute("disabled", this.disabled);
        this._button.disabled = this.disabled || !this.value;
    }
}
