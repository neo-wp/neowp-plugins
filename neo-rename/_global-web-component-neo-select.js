import { pluginUrl } from "./_global-plugin-and-uploads-url.js";

export default class NeoSelect extends HTMLElement {
    static get observedAttributes() { return ["disabled", "value", "selected-display"]; }

    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._closeOnOutsideClick = this._closeOnOutsideClick.bind(this);
        this._syncOptions = this._syncOptions.bind(this);
        this._onTriggerClick = this._onTriggerClick.bind(this);
        this._onTriggerKeydown = this._onTriggerKeydown.bind(this);
        this._positionListbox = this._positionListbox.bind(this);
        this._trackListboxPosition = this._trackListboxPosition.bind(this);
        this._positionAnimationFrame = null;
        this._lastTriggerPosition = "";
        this._mutationObserver = new MutationObserver(this._syncOptions);
        this.shadowRoot.innerHTML = `
            <style>
                :host { display: inline-block; position: relative; font-family: sans-serif; font-size: 14px; min-width: 3.6em; color: #1d2327; }
                :host([selected-display="empty"]) { min-width: 0; }
                :host([disabled]) { color: #949494; pointer-events: none; }
                .trigger {
                    display: flex; align-items: center; justify-content: space-between; gap: 0.5em;
                    width: 100%; height: 100%; min-height: 2.4em; box-sizing: border-box;
                    border: 1px solid var(--neo-select-border-color-neo-rename, #A0A0A0); border-radius: var(--neo-select-border-radius-neo-rename, 4px);
                    background: var(--neo-select-background-neo-rename, #ffffff); color: inherit; font: inherit;
                    padding: var(--neo-select-padding-neo-rename, 0.45em 1.9em 0.45em 0.65em); cursor: pointer; text-align: left; white-space: nowrap;
                }
                :host([disabled]) .trigger { background: #f0f0f0; cursor: default; }
                .trigger:focus-visible { outline: 1px solid blue; outline-offset: 0; z-index: 1; }
                .trigger::after { content: ""; position: absolute; right: 0.7em; width: 0; height: 0; border-left: 0.32em solid transparent; border-right: 0.32em solid transparent; border-top: 0.42em solid currentColor; pointer-events: none; opacity: 0.75; }
                .selected-label { display: block; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
                .selected-icon, .option-icon { display: inline-block; width: 1.2em; height: 1.2em; flex: 0 0 1.2em; background-color: currentColor; mask: var(--icon-url) center / contain no-repeat; -webkit-mask: var(--icon-url) center / contain no-repeat; }
                .selected-icon-crown, .option-icon-crown { background: var(--icon-url) center / contain no-repeat; mask: none; -webkit-mask: none; }
                .selected-icon:not([style*="--icon-url"]), .option-icon:not([style*="--icon-url"]) { display: none; }
                .listbox { position: fixed; inset: auto;top: var(--listbox-top, 0px); left: var(--listbox-left, 0px); z-index: 200001;display: none; }
                .listbox { width: var(--listbox-width, max(100%, 14em)); min-width: var(--listbox-min-width, 14em); max-width: min(28em, calc(100vw - 20px)); max-height: var(--listbox-max-height, min(520px, calc(100vh - 40px))); overflow: auto; overscroll-behavior: auto; }
                .listbox { box-sizing: border-box; border: 1px solid #A0A0A0; border-radius: 4px; background: #ffffff; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18); padding: 4px; margin: 0; }
                :host([open]) .listbox { display: block; }
                .listbox:popover-open { display: block; }
                .option, .group-label { display: block; box-sizing: border-box; width: 100%; border: 0; background: transparent; color: inherit; font: inherit; text-align: left; white-space: nowrap; }
                .option { display: flex; align-items: center; gap: 0.5em; padding: 0.45em 0.65em; border-radius: 3px; cursor: pointer; }
                .option-label { display: block; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
                .option:hover { background: #f1f5f9; color: #1d2327; }
                .option[aria-selected="true"] { background: #e7f5ff; color: #135e96; font-weight: 700; }
                .option[aria-selected="true"]:hover { background: #d8ecfb; }
                .option:focus-visible { outline: 1px solid blue; }
                .group-label { padding: 0.45em 0.65em 0.2em; color: #606060; font-weight: 700; cursor: default; }
            </style>
            <button class="trigger" part="trigger" type="button" aria-haspopup="listbox" aria-expanded="false"><span class="selected-icon" part="selected-icon"></span><span class="selected-label" part="selected-label"></span></button>
            <div class="listbox" part="listbox" role="listbox" popover="manual"></div>
            <slot style="display:none;"></slot>
        `;
        this._trigger = this.shadowRoot.querySelector(".trigger");
        this._selectedIcon = this.shadowRoot.querySelector(".selected-icon");
        this._selectedLabel = this.shadowRoot.querySelector(".selected-label");
        this._listbox = this.shadowRoot.querySelector(".listbox");
        this._slot = this.shadowRoot.querySelector("slot");
    }

    connectedCallback() {
        this._trigger.addEventListener("click", this._onTriggerClick);
        this._trigger.addEventListener("keydown", this._onTriggerKeydown);
        this._slot.addEventListener("slotchange", this._syncOptions);
        this._mutationObserver.observe(this, { childList: true, subtree: true, attributes: true, attributeFilter: ["selected", "value", "label", "disabled", "data-icon-url", "show-pro-crown-if-not-pro"] });
        this._syncOptions();
        this._syncDisabledState();
    }

    disconnectedCallback() {
        this._trigger.removeEventListener("click", this._onTriggerClick);
        this._trigger.removeEventListener("keydown", this._onTriggerKeydown);
        this._slot.removeEventListener("slotchange", this._syncOptions);
        this._mutationObserver.disconnect();
        document.removeEventListener("click", this._closeOnOutsideClick, true);
        window.removeEventListener("resize", this._positionListbox, true);
        if (this._positionAnimationFrame !== null) { cancelAnimationFrame(this._positionAnimationFrame); this._positionAnimationFrame = null; }
    }

    attributeChangedCallback(name) {
        if (name === "disabled") { this._syncDisabledState(); }
        if (name === "value") { this.value = this.getAttribute("value") ?? ""; }
        if (name === "selected-display") { this._syncSelectedLabel(); }
    }

    get disabled() { return this.hasAttribute("disabled"); }
    set disabled(isDisabled) { this.toggleAttribute("disabled", Boolean(isDisabled)); }
    get options() { return [...this.querySelectorAll("option")]; }
    get selectedOptions() { return this.options.filter((optionNode) => optionNode.selected || optionNode.hasAttribute("selected")); }
    get value() { return this.selectedOptions[0]?.value ?? this.getAttribute("value") ?? ""; }
    set value(newValue) {
        const stringValue = newValue ?? "";
        let hasSelectedOption = false;
        for (const optionNode of this.options) {
            const isSelected = optionNode.value === stringValue;
            optionNode.selected = isSelected;
            optionNode.toggleAttribute("selected", isSelected);
            hasSelectedOption ||= isSelected;
        }
        if (hasSelectedOption && this.getAttribute("value") !== stringValue) { this.setAttribute("value", stringValue); }
        if (!hasSelectedOption && this.hasAttribute("value")) { this.removeAttribute("value"); }
        this._syncSelectedLabel();
        this._syncRenderedSelection();
    }

    _onTriggerClick(event) {
        event.stopPropagation();
        if (this.disabled) { return; }
        if (this.hasAttribute("open")) { this._closeDropdown(); return; }
        this._openDropdown();
    }

    _onTriggerKeydown(event) {
        if (this.disabled) { return; }
        if (event.key === "Escape") { this._closeDropdown(); event.preventDefault(); }
        if (event.key === "Enter" || event.key === " ") { this._onTriggerClick(event); event.preventDefault(); }
        if (event.key === "ArrowDown" || event.key === "ArrowUp") { this._moveSelection(event.key === "ArrowDown" ? 1 : -1); event.preventDefault(); }
    }

    _closeOnOutsideClick(event) {
        if (event.composedPath().includes(this) || event.composedPath().includes(this._listbox)) { return; }
        this._closeDropdown();
    }

    _openDropdown() {
        this.setAttribute("open", "");
        this._trigger.setAttribute("aria-expanded", "true");
        this._listbox.style.visibility = "hidden";
        if (this._listbox.showPopover) { this._listbox.showPopover(); }
        this._positionListbox();
        this._lastTriggerPosition = "";
        this._positionAnimationFrame = requestAnimationFrame(this._trackListboxPosition);
        document.addEventListener("click", this._closeOnOutsideClick, true);
        window.addEventListener("resize", this._positionListbox, true);
    }

    _closeDropdown() {
        this.removeAttribute("open");
        this._trigger.setAttribute("aria-expanded", "false");
        if (this._listbox.hidePopover && this._listbox.matches(":popover-open")) { this._listbox.hidePopover(); }
        document.removeEventListener("click", this._closeOnOutsideClick, true);
        window.removeEventListener("resize", this._positionListbox, true);
        if (this._positionAnimationFrame !== null) { cancelAnimationFrame(this._positionAnimationFrame); this._positionAnimationFrame = null; }
        this._listbox.style.visibility = "";
    }

    _trackListboxPosition() {
        if (!this.hasAttribute("open")) { return; }
        const triggerRect = this._trigger.getBoundingClientRect();
        const triggerPosition = `${triggerRect.left},${triggerRect.top},${triggerRect.width},${triggerRect.height}`;
        if (triggerPosition !== this._lastTriggerPosition) { this._lastTriggerPosition = triggerPosition; this._positionListbox(); }
        this._listbox.style.visibility = "";
        this._positionAnimationFrame = requestAnimationFrame(this._trackListboxPosition);
    }

    _positionListbox() {
        const triggerRect = this._trigger.getBoundingClientRect();
        const viewportPadding = 10;
        const oldDisplay = this._listbox.style.display;
        const oldVisibility = this._listbox.style.visibility;
        this._listbox.style.display = "block";
        this._listbox.style.visibility = "hidden";
        const listboxStyle = getComputedStyle(this._listbox);
        const listboxMaxWidth = Math.min((parseFloat(listboxStyle.fontSize) || 14) * 28, window.innerWidth - viewportPadding * 2);
        const listboxChromeWidth = (parseFloat(listboxStyle.paddingLeft) || 0) + (parseFloat(listboxStyle.paddingRight) || 0) + (parseFloat(listboxStyle.borderLeftWidth) || 0) + (parseFloat(listboxStyle.borderRightWidth) || 0);
        const listboxNaturalContentWidth = Math.max(0, ...[...this._listbox.children].map((node) => {
            const nodeStyle = getComputedStyle(node);
            const nodePaddingWidth = (parseFloat(nodeStyle.paddingLeft) || 0) + (parseFloat(nodeStyle.paddingRight) || 0);
            const optionIconWidth = node.querySelector(".option-icon")?.getBoundingClientRect().width || 0;
            const optionGapWidth = optionIconWidth ? (parseFloat(nodeStyle.columnGap || nodeStyle.gap) || 0) : 0;
            const optionLabelWidth = node.querySelector(".option-label")?.scrollWidth ?? node.scrollWidth;
            return nodePaddingWidth + optionIconWidth + optionGapWidth + optionLabelWidth;
        }));
        const listboxWidth = Math.ceil(Math.min(Math.max(triggerRect.width, 224, listboxNaturalContentWidth + listboxChromeWidth), listboxMaxWidth));
        const listboxLeft = Math.min(Math.max(triggerRect.left, viewportPadding), window.innerWidth - listboxWidth - viewportPadding);
        const spaceBelow = window.innerHeight - triggerRect.bottom - viewportPadding - 3;
        const spaceAbove = triggerRect.top - viewportPadding - 3;
        const shouldOpenAbove = spaceBelow < 320 && spaceAbove > spaceBelow;
        const availableSpace = Math.max(80, shouldOpenAbove ? spaceAbove : spaceBelow);
        const listboxMaxHeight = Math.min(520, availableSpace);
        const listboxHeight = Math.min(listboxMaxHeight, this._listbox.scrollHeight);
        this._listbox.style.display = oldDisplay;
        this._listbox.style.visibility = oldVisibility;
        const listboxTop = Math.min(Math.max(shouldOpenAbove ? triggerRect.top - listboxHeight - 3 : triggerRect.bottom + 3, viewportPadding), window.innerHeight - listboxHeight - viewportPadding);
        this._listbox.style.setProperty("--listbox-top", `${listboxTop}px`);
        this._listbox.style.setProperty("--listbox-left", `${listboxLeft}px`);
        this._listbox.style.setProperty("--listbox-width", `${listboxWidth}px`);
        this._listbox.style.setProperty("--listbox-min-width", `${triggerRect.width}px`);
        this._listbox.style.setProperty("--listbox-max-height", `${listboxMaxHeight}px`);
    }

    _moveSelection(direction) {
        const enabledOptions = this.options.filter((optionNode) => !optionNode.disabled);
        if (!enabledOptions.length) { return; }
        const selectedIndex = Math.max(0, enabledOptions.findIndex((optionNode) => optionNode.value === this.value));
        this._selectOption(enabledOptions[(selectedIndex + direction + enabledOptions.length) % enabledOptions.length]);
    }

    _selectOption(optionNode) {
        if (!optionNode || optionNode.disabled) { return; }
        this.value = optionNode.value;
        this._closeDropdown();
        this.dispatchEvent(new Event("change", { bubbles: true }));
    }

    _syncOptions() {
        this._listbox.replaceChildren();
        for (const node of this.children) {
            if (node.tagName === "OPTGROUP") { this._renderOptgroup(node); }
            if (node.tagName === "OPTION") { this._renderOption(node); }
        }
        if (!this.selectedOptions.length && this.options.length) { this.value = this.getAttribute("value") ?? this.options[0].value; }
        this._syncSelectedLabel();
        this._syncRenderedSelection();
    }

    _renderOptgroup(optgroupNode) {
        const labelNode = document.createElement("div");
        labelNode.className = "group-label";
        labelNode.textContent = optgroupNode.label;
        this._listbox.appendChild(labelNode);
        for (const optionNode of optgroupNode.querySelectorAll("option")) { this._renderOption(optionNode); }
    }

    _renderOption(optionNode) {
        const buttonNode = document.createElement("button");
        buttonNode.className = "option";
        buttonNode.part = "option";
        buttonNode.type = "button";
        const iconNode = document.createElement("span");
        iconNode.className = "option-icon";
        iconNode.classList.toggle("option-icon-crown", this._shouldShowProCrown(optionNode));
        if (this._optionIconUrl(optionNode)) { iconNode.style.setProperty("--icon-url", `url("${this._optionIconUrl(optionNode)}")`); }
        const labelNode = document.createElement("span");
        labelNode.className = "option-label";
        labelNode.textContent = optionNode.textContent;
        buttonNode.append(iconNode, labelNode);
        buttonNode.setAttribute("role", "option");
        buttonNode.setAttribute("data-value", optionNode.value);
        buttonNode.disabled = optionNode.disabled;
        buttonNode.addEventListener("click", (event) => { event.stopPropagation(); this._selectOption(optionNode); });
        this._listbox.appendChild(buttonNode);
    }

    _syncSelectedLabel() {
        const selectedText = this.selectedOptions[0]?.textContent.trim() ?? "";
        const selectedIconUrl = this._optionIconUrl(this.selectedOptions[0]);
        this._selectedIcon.classList.toggle("selected-icon-crown", this._shouldShowProCrown(this.selectedOptions[0]));
        if (selectedIconUrl) { this._selectedIcon.style.setProperty("--icon-url", `url("${selectedIconUrl}")`); } else { this._selectedIcon.style.removeProperty("--icon-url"); }
        if (selectedIconUrl && this.getAttribute("selected-display") === "first-token") { this._selectedLabel.textContent = ""; return; }
        if (this.getAttribute("selected-display") === "first-token") { this._selectedLabel.textContent = selectedText.split(" ")[0] ?? ""; return; }
        if (this.getAttribute("selected-display") === "empty") { this._selectedLabel.textContent = ""; return; }
        this._selectedLabel.textContent = selectedText;
    }

    _syncRenderedSelection() {
        for (const renderedOption of this._listbox.querySelectorAll(".option")) { renderedOption.setAttribute("aria-selected", renderedOption.getAttribute("data-value") === this.value ? "true" : "false"); }
    }

    _syncDisabledState() {
        this._trigger.disabled = this.disabled;
        this._trigger.setAttribute("aria-disabled", this.disabled ? "true" : "false");
    }

    _optionIconUrl(optionNode) {
        if (!optionNode) { return ""; }
        if (this._shouldShowProCrown(optionNode)) { return pluginUrl() + "/img/plugin-neo-all-icon-crown.svg"; }
        return optionNode.getAttribute("data-icon-url") ?? "";
    }

    _shouldShowProCrown(optionNode) {
        let hasPro = false; 
        return (optionNode?.hasAttribute("show-pro-crown-if-not-pro") ?? false) && !hasPro;
    }
}

if (!customElements.get("neo-select-neo-rename")) { customElements.define("neo-select-neo-rename", NeoSelect); }
