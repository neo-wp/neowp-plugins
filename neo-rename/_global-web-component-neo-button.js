import { pricingUrl } from "./_global-pricing-url.js";

export default class NeoButton extends HTMLElement {
  static get observedAttributes() {
    return ["href", "target", "disabled", "only-enabled-if-pro", "show-pro-crown", "success", "loading"];
  }

  constructor() {
    super();
    this.attachShadow({ mode: "open" });
    this.shadowRoot.innerHTML = `
      <style>
        :host { display:inline-block; }

        .btn {
            background: var(--wp-admin-theme-color, #2271b1);
            border-color: var(--wp-admin-theme-color, #2271b1);
            color: #fff;
            text-decoration: none;
            text-shadow: none;
            display: inline-block;
            text-decoration: none;
            font-size: 0.9em;
            line-height: 1.2;
            padding: 0.8em 1em;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
            -webkit-appearance: none;
            border-radius: 10px;
            white-space: nowrap;
            box-sizing: border-box;
            position: relative;
            &:not(:disabled):hover { filter: brightness(110%); }
        }

        .btn[success] {
          background: #28A745;
          border-color: #28A745;
        }

        .btn[disabled], .btn[aria-disabled="true"] {
          opacity:.6; cursor:not-allowed; pointer-events:none;
        }

        .btn-content { display:inline-flex; align-items:baseline; gap:0.3em; }
        .btn.has-crown .btn-content { gap:0.45em; }
        .pro-crown { width:1em; height:1em; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; align-self:baseline; transform:translateY(0.08em); margin-top: -100%; }

        .btn[loading] .btn-content { color: transparent; }
        .btn[loading] .btn-content > * { opacity: 0; }
        .btn[loading]::after {
          content: "";
          position: absolute; top: 50%; left: 50%;
          transform: translate(-50%, -50%);
          width: 15px; height: 15px; border-radius: 50%;
          border: 3px solid rgba(255, 255, 255, .2);
          border-top-color: rgba(255, 255, 255, .8);
          animation: neo-global--button-loading-animation 1s linear infinite;
        }
        @keyframes neo-global--button-loading-animation {
          from { transform: translate(-50%, -50%) rotate(0deg); }
          to   { transform: translate(-50%, -50%) rotate(360deg); }
        }
      </style>

      <span class="root"></span>
    `;

    this._root = this.shadowRoot.querySelector(".root");
    this._clickHandler = this._onInternalButtonClick.bind(this);
    this._linkClickHandler = this._onInternalLinkClick.bind(this);
    this._hostClickHandler = this._onHostClick.bind(this);
    this.addEventListener("click", this._hostClickHandler, { capture: true });
    this._ensureButton();
    this._applyDisabled();
    this._applySuccess();
    this._applyLoading();
  }

  connectedCallback() { this._applyProState(); }

  attributeChangedCallback(name) {
    if (name === "disabled") this._applyDisabled();
    if (name === "href" || name === "target" || name === "only-enabled-if-pro") this._applyProState();
    if (name === "show-pro-crown") this._applyCrown();
    if (name === "success") this._applySuccess();
    if (name === "loading") this._applyLoading();
  }

  get disabled() { return this.hasAttribute("disabled"); }
  set disabled(v) { v ? this.setAttribute("disabled", "") : this.removeAttribute("disabled"); }

  _applyDisabled() {
    const node = this._getInteractiveNode();
    if (!node) return;
    this._updateInteractivity(node);
  }

  _applySuccess() {
    const node = this._getInteractiveNode();
    if (!node) return;
    if (this.hasAttribute("success")) {
      node.setAttribute("success", "");
    } else {
      node.removeAttribute("success");
    }
  }

  _applyLoading() {
    const node = this._getInteractiveNode();
    if (!node) return;
    if (this.hasAttribute("loading")) {
      node.setAttribute("loading", "");
    } else {
      node.removeAttribute("loading");
    }
    this._updateInteractivity(node);
  }

  _updateInteractivity(node = this._getInteractiveNode()) {
    if (!node) return;
    const blockInteraction = this._isInteractionBlocked();
    if (node.tagName === "BUTTON") {
      node.disabled = blockInteraction;
      blockInteraction ? node.setAttribute("aria-disabled", "true") : node.removeAttribute("aria-disabled");
      this.hasAttribute("loading") ? node.setAttribute("aria-busy", "true") : node.removeAttribute("aria-busy");
      return;
    }

    if (blockInteraction) {
      if (!node.dataset.originalHref) node.dataset.originalHref = node.getAttribute("href") ?? "";
      node.removeAttribute("href");
      node.setAttribute("aria-disabled", "true");
      if (this.hasAttribute("loading")) node.setAttribute("aria-busy", "true"); else node.removeAttribute("aria-busy");
      node.setAttribute("tabindex", "-1");
      node.style.pointerEvents = "none";
      return;
    }

    node.removeAttribute("aria-disabled");
    node.removeAttribute("aria-busy");
    node.removeAttribute("tabindex");
    node.style.pointerEvents = "";

    const restoredHref = node.dataset.originalHref;
    if (restoredHref) node.href = restoredHref;
    delete node.dataset.originalHref;
    this._syncHrefOnLinkIfAny();
  }

  _applyProState() {
    const href = this._getLinkHref();
    if (href) {
      this._ensureLink(href);
    } else {
      this._ensureButton();
    }
    this._applyCrown();
    this._applyDisabled();
    this._applySuccess();
    this._applyLoading();
  }

  _ensureButton() {
    const current = this._getInteractiveNode();
    if (current && current.tagName === "BUTTON") return;

    const btn = document.createElement("button");
    btn.className = "btn";
    btn.type = "button";
    btn.part = "button";
    btn.appendChild(this._buildContentSpan());

    if (this.hasAttribute("success")) btn.setAttribute("success", "");
    if (this.hasAttribute("loading")) btn.setAttribute("loading", "");

    if (current) {
      if (current.tagName === "BUTTON") current.removeEventListener("click", this._clickHandler);
      if (current.tagName === "A") current.removeEventListener("click", this._linkClickHandler);
      current.replaceWith(btn);
    } else {
      this._root.appendChild(btn);
    }
    btn.addEventListener("click", this._clickHandler);
  }

  _ensureLink(href) {
    const current = this._getInteractiveNode();
    if (current && current.tagName === "A") {
      current.href = href;
      this._syncTargetOnLink(current);
      if (this.hasAttribute("success")) current.setAttribute("success", ""); else current.removeAttribute("success");
      if (this.hasAttribute("loading")) current.setAttribute("loading", ""); else current.removeAttribute("loading");
      current.removeEventListener("click", this._linkClickHandler);
      current.addEventListener("click", this._linkClickHandler);
      return;
    }
    const a = document.createElement("a");
    a.className = "btn";
    a.href = href; a.part = "button";
    this._syncTargetOnLink(a);
    a.appendChild(this._buildContentSpan());

    if (this.hasAttribute("success")) a.setAttribute("success", "");
    if (this.hasAttribute("loading")) a.setAttribute("loading", "");

    if (current) {
      if (current.tagName === "BUTTON") current.removeEventListener("click", this._clickHandler);
      current.replaceWith(a);
    } else {
      this._root.appendChild(a);
    }
    a.addEventListener("click", this._linkClickHandler);
  }

  _syncHrefOnLinkIfAny() {
    const node = this._getInteractiveNode();
    if (node && node.tagName === "A") {
      node.href = this._getLinkHref() ?? "";
      this._syncTargetOnLink(node);
    }
  }

  _getLinkHref() {
    if (this.hasAttribute("only-enabled-if-pro")) {
      return pricingUrl(); 
    }
    return this.getAttribute("href");
  }

  _getLinkTarget() {
    if (this.hasAttribute("only-enabled-if-pro")) {
      return "_blank"; 
    }
    return this.getAttribute("target");
  }

  _syncTargetOnLink(node) {
    const target = this._getLinkTarget();
    if (target) { node.target = target; } else { node.removeAttribute("target"); }
    if (target === "_blank") { node.rel = "noopener"; } else { node.removeAttribute("rel"); }
  }

  _buildContentSpan() {
    const existing = this._getContentSpan();
    if (existing) return existing;
    const span = document.createElement("span");
    span.className = "btn-content";
    const slot = document.createElement("slot");
    span.appendChild(slot);
    return span;
  }

  _getInteractiveNode() { return this._root.firstElementChild || null; }
  _isInteractionBlocked() { return this.disabled || this.hasAttribute("loading"); }
  _getContentSpan() {
    const node = this._getInteractiveNode();
    return node ? node.querySelector(".btn-content") : null;
  }

  _shouldShowCrown() {
    if (this.hasAttribute("show-pro-crown")) { return true; }
    if (this.hasAttribute("only-enabled-if-pro")) return true; 
    return false;
  }
  _applyCrown() {
    if (this._shouldShowCrown()) {
      this._ensureCrown();
    } else {
      this._removeCrown();
    }
  }

  _ensureCrown() {
    const content = this._getContentSpan() || this._buildContentSpan();
    let crown = content.querySelector(".pro-crown");
    if (!crown) {
      crown = document.createElement("neo-pro-crown-neo-rename");
      crown.classList.add("pro-crown");
      content.prepend(crown);
    }
    const node = this._getInteractiveNode();
    if (node) node.classList.add("has-crown");
  }
  _removeCrown() {
    const content = this._getContentSpan();
    const crown = content && content.querySelector(".pro-crown");
    if (crown) crown.remove();
    const node = this._getInteractiveNode();
    if (node) node.classList.remove("has-crown");
  }

  _onInternalButtonClick() {
    const node = this._getInteractiveNode();
    if (!node || node.tagName !== "BUTTON") return;
    if (this._isInteractionBlocked()) return;
  }

  _onHostClick(event) {
    if (!this._isInteractionBlocked()) return;
    event.preventDefault();
    event.stopImmediatePropagation();
  }

  _onInternalLinkClick(event) {
    const node = this._getInteractiveNode();
    if (!node || node.tagName !== "A") return;
    if (this.hasAttribute("only-enabled-if-pro")) {
        event.stopPropagation();
    }
  }
}
