export default class NeoSetting extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.updateVisibility = this.updateVisibility.bind(this);
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: flex; flex-direction: column; gap: 10px;
                }
                .topRow {
                    display: flex; justify-content: space-between; gap: 20px;
                    align-items: center;
                }

                ::slotted([slot="left"]) { display: flex; flex-direction: column; gap: 6px; }
                @media (max-width: 767px) {
                    .topRow { flex-direction: column; align-items: flex-start; }
                }
            </style>

            <div class="topRow">
                <slot name="left"></slot>
                <slot name="right"></slot>
            </div>
            <slot></slot>
        `;

        this._topRow = this.shadowRoot.querySelector(".topRow");
        this._leftSlot = this.shadowRoot.querySelector('slot[name="left"]');
        this._rightSlot = this.shadowRoot.querySelector('slot[name="right"]');
        this._mainSlot = this.shadowRoot.querySelector("slot:not([name])");
    }

    connectedCallback() {
        this.setAttribute("data-neo-global--setting-style-scope", "neo-replace");
        if (!document.querySelector("style#data-neo-global--setting-style-scope-neo-replace")) {
            const style = document.createElement("style");
            style.id = "data-neo-global--setting-style-scope-neo-replace";
            style.innerHTML = `
            [data-neo-global--setting-style-scope="neo-replace"] {
                h3 { margin: 0; line-height: 1; } h4 { margin: 0; line-height: 1; }
                p  { margin: 0; }
                ul { margin: 0; padding: 0; list-style: none; } li { margin: 0; padding: 0; }
                button { background: none; border: none; cursor: pointer; }
                a { text-decoration: none; color: inherit; }
            }
            `;
            document.head.appendChild(style);
        }

        this.updateVisibility();
        this._leftSlot.addEventListener("slotchange", this.updateVisibility);
        this._rightSlot.addEventListener("slotchange", this.updateVisibility);
        this._mainSlot.addEventListener("slotchange", this.updateVisibility);
    }

    updateVisibility() {
        const hasContent = slot => slot.assignedNodes({ flatten: true }).some(node => node.nodeType === 1 || (node.nodeType === 3 && node.textContent.trim() !== ""));
        this._leftSlot.style.display = hasContent(this._leftSlot) ? "" : "none";
        this._rightSlot.style.display = hasContent(this._rightSlot) ? "" : "none";
        this._mainSlot.style.display = hasContent(this._mainSlot) ? "" : "none";
        this._topRow.style.display = !hasContent(this._leftSlot) && !hasContent(this._rightSlot) ? "none" : "";
        this.style.display = !hasContent(this._leftSlot) && !hasContent(this._rightSlot) && !hasContent(this._mainSlot) ? "none" : "";
    }

    disconnectedCallback() {
        this._leftSlot?.removeEventListener("slotchange", this.updateVisibility);
        this._rightSlot?.removeEventListener("slotchange", this.updateVisibility);
        this._mainSlot?.removeEventListener("slotchange", this.updateVisibility);
    }
}