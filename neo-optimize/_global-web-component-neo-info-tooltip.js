import { neo__ } from "./_global--translation.js";

const HOVER_DELAY_MS       = 1000;
const HOVER_DELAY_CLOSE_MS = 300;
const TOUCH_DRAG_THRESHOLD_PX = 8;
const MARGIN_PX            = 10;
const BODY_CSS_ID          = "neo-info-tooltip-global-styles-neo-optimize";
let noClickHoverSuppressionRect = null;
let noClickHoverSuppressionAbortController = null;
function pointerIsInsideRect(event, rect) { return event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom; }

export default class NeoInfoTooltip extends HTMLElement {
  #hoverTimer      = null;
  #leaveTimer      = null;
  #openedByClick   = false;
  #eventsBound     = false;
  #isSyncingOpen   = false;
  #pendingOpenAttr = false;
  #outsideClickHdl = this.#handleOutsideClick.bind(this);
  #escHdl          = this.#handleEsc.bind(this);
  #repositionHdl   = this.#reposition.bind(this);
  #windowBlurHdl   = this.#handleWindowBlur.bind(this);
  #visibilityHdl   = this.#handleVisibilityChange.bind(this);
  #touchPointerId  = null;
  #touchStartX     = 0;
  #touchStartY     = 0;
  #touchStartScrollX = 0;
  #touchStartScrollY = 0;
  #touchMoved      = false;
  #suppressNextClick = false;
  #suppressHoverUntilLeave = false;
  #contentObserver = null;

  static get observedAttributes () { return ["default-icon-color","disabled","open","no-click-open"]; }

  get disabled () { return this.hasAttribute("disabled"); }
  set disabled (v) {
    if (v) this.setAttribute("disabled","");
    else   this.removeAttribute("disabled");
  }

  constructor () {
    super();
    this.attachShadow({ mode: "open" });
    this.shadowRoot.innerHTML =`
      <style>
        :host{
          display:inline-block; position:relative; line-height:1;
          font-family:system-ui,sans-serif;
          --neo-global--icon-color:#000;
          --neo-global--icon-bg:#ffffff00;
          --neo-global--icon-bg-hover:#d9d9d9ff;
          --neo-global--arrow-w:14px; --neo-global--arrow-h:4px;
        }
        .icon{
          cursor:pointer; user-select:none;
        }
        .default-icon {
          width:1.2em;height:1.2em;
          display:inline-flex;align-items:center;justify-content:center;
          font-size:1em;
          color:var(--neo-global--icon-color);background:var(--neo-global--icon-bg);
          border-radius:50%;font-weight:600;transition:background .2s,color .2s;
        }
        .default-icon:hover,.default-icon:active{ background:var(--neo-global--icon-bg-hover); }
        :host(:focus-visible) .default-icon{
          outline:2px solid #2684ff; outline-offset:2px; border-radius:50%;
        }
      </style>

      <span class="icon" part="icon" aria-hidden="false"><slot name="icon"><span class="default-icon" part="icon" aria-hidden="false">ⓘ</span></slot></span>

      <div class="neo-global--tooltip" part="tooltip" role="tooltip">
        <span class="close" part="close" aria-label="${neo__("Close","Schließen")}">&times;</span>
        <div class="content"></div>
      </div>
    `;

    this._iconEl     = this.shadowRoot.querySelector(".icon");
    this._iconSlotEl = this.shadowRoot.querySelector('slot[name="icon"]');
    this._tooltipEl  = this.shadowRoot.querySelector(".neo-global--tooltip");
    this._contentEl  = this.shadowRoot.querySelector(".content");
    this._closeEl    = this.shadowRoot.querySelector(".close");
  }

  attributeChangedCallback (name) {
    if (name === "default-icon-color") {
      this.#syncDefaultIconColor();
    }
    if (name === "disabled") {
      this.#syncDisabledState();
      this.#syncAria();

      if (this.disabled) {
        this.close();
        this.#clearHoverTimer();
        this.#clearLeaveTimer();
      }
    }
    if (name === "open") {
      const wantsOpen = this.hasAttribute("open");
      if (!this.isConnected) { this.#pendingOpenAttr = wantsOpen; return; }
      if (this.#isSyncingOpen) return;
      if (this.disabled && wantsOpen) {
        this.#isSyncingOpen = true;
        this.removeAttribute("open");
        this.#isSyncingOpen = false;
        return;
      }
      wantsOpen ? this.open() : this.close();
    }
    if (name === "no-click-open") {
      this.#syncAria();
      if (this.hasAttribute("no-click-open") && this.#openedByClick) {
        this.close();
      }
    }
  }

  connectedCallback () {
    this.#syncAria();
    this.#syncDisabledState();
    this.#syncDefaultIconColor();

    if (!document.getElementById(BODY_CSS_ID)) {
      const globalCss = document.createElement("style");
      globalCss.id = BODY_CSS_ID;
      globalCss.textContent =`
        .neo-global--tooltip-neo-optimize{
          position:fixed; left:0; top:0;
          width:max-content; min-width:180px; max-width:min(500px,calc(100vw - 8px));
          padding: 10px 20px; background:#fff; color:#222;
          box-shadow:0 6px 22px rgba(0,0,0,.14),0 2px 8px rgba(0,0,0,.08);
          border-radius:12px; box-sizing:border-box;
          font-size:14px; line-height:1.35;
          opacity:0; pointer-events:none;
          transition:opacity .24s ease,transform .24s ease;
          will-change:opacity,transform;
          z-index:200001;

          &[data-click-open] { padding-right: 30px; }
          &[data-visible]{ opacity:1; pointer-events:auto; }
          &[data-instant-hover]{ transition:none; }

          &[data-placement="bottom"]{ transform:translateY(-4px); }
          &[data-visible][data-placement="bottom"]{ transform:none; }
          &[data-placement="top"]{ transform:translateY(4px); }
          &[data-visible][data-placement="top"]{ transform:none; }

          &::before{
            content:""; position:absolute; width:14px; height:4px; background:#fff;
            left:var(--neo-global--tooltip-arrow-left, 50%); transform-origin:center;
            clip-path:polygon(50% 0%,0% 100%,100% 100%);
            box-shadow:0 0 1px rgba(0,0,0,.04);
          }
          &[data-placement="top"]::before{
            bottom:-1px;
            transform:translateX(-50%) rotate(180deg);
          }
          &[data-placement="bottom"]::before{
            top:-1px;
            transform:translateX(-50%) translateY(calc(-100% + 1px));
          }

          .neo-global--close-neo-optimize {
            position:absolute; top: 0; right: 0; padding: 10px; padding-top: 6px; padding-right: 8px; font-size: 1em; line-height: 1;
            cursor:pointer; user-select:none;
            display:none;
          }
          &[data-click-open] .neo-global--close-neo-optimize{ display:block; }

          .content { display:flex; flex-direction:column; align-items:center; text-align:center; }

          h3 { margin: 0 0 0.3em 0; }
          h4 { margin: 0.8em 0 0.2em 0; }
          ul { margin: 0; }

          ul { list-style-type:disc; padding-left:1.5em; }
        }
      `;
      document.head.appendChild(globalCss);
    }

    this._tooltipEl.classList.add("neo-global--tooltip-neo-optimize");
    this._closeEl.classList.add("neo-global--close-neo-optimize");
    document.body.appendChild(this._tooltipEl);

    this.#projectContent();
    this.#bindContentObserver();

    this.#bindEvents();
    if (this.hasAttribute("open") || this.#pendingOpenAttr) {
      this.#pendingOpenAttr = false;
      this.open();
    }
  }

  disconnectedCallback () {
    this.close();
    this.#suppressHoverUntilLeave = false;
    this.#clearHoverTimer();
    this.#clearLeaveTimer();
    this.#unbindOutside();
    this.#unbindFocusLoss();
    this.#unbindViewport();
    this.#unbindContentObserver();
    this._tooltipEl.remove();
  }

  open () {
    if (this.disabled) return;
    if (!this.hasAttribute("open")) {
      this.#isSyncingOpen = true;
      this.setAttribute("open","");
      this.#isSyncingOpen = false;
    }
    this._tooltipEl.dataset.visible = "";
    if (this.hasAttribute("instant-hover")) this._tooltipEl.dataset.instantHover = "";
    else                                    delete this._tooltipEl.dataset.instantHover;
    if (this.#openedByClick) this._tooltipEl.dataset.clickOpen = "";
    else                     delete this._tooltipEl.dataset.clickOpen;
    this.#syncAria();
    this.#bindOutside();
    this.#bindFocusLoss();
    this.#reposition();
    this.#bindViewport();
  }
  close () {
    if (this.hasAttribute("open")) {
      this.#isSyncingOpen = true;
      this.removeAttribute("open");
      this.#isSyncingOpen = false;
    } else if (!this._tooltipEl.hasAttribute("data-visible")) {
      return;
    }
    if (this.hasAttribute("instant-hover")) this._tooltipEl.dataset.instantHover = "";
    else                                    delete this._tooltipEl.dataset.instantHover;
    delete this._tooltipEl.dataset.visible;
    delete this._tooltipEl.dataset.clickOpen;
    this.#openedByClick = false;
    this.#syncAria();
    this.#unbindOutside();
    this.#unbindFocusLoss();
    this.#unbindViewport();
    this.#clearHoverTimer();
    this.#clearLeaveTimer();
  }
  toggle (force) {
    if (this.disabled) return;
    (typeof force === "boolean")
      ? (force ? this.open() : this.close())
      : (this.hasAttribute("open") ? this.close() : this.open());
  }

  #bindEvents () {
    if (this.#eventsBound) return;
    this.#eventsBound = true;
    this._iconEl.addEventListener("pointerdown", e => this.#handleIconPointerDown(e));
    this._iconEl.addEventListener("pointermove", e => this.#handleIconPointerMove(e));
    this._iconEl.addEventListener("pointerup", e => this.#handleIconPointerEnd(e));
    this._iconEl.addEventListener("pointercancel", e => this.#handleIconPointerEnd(e));
    this._iconEl.addEventListener("click", e => {
      if (this.disabled || !this.hasAttribute("no-click-open")) { return; }
      this.#suppressHoverUntilLeave = e.detail > 0;
      if (this.#suppressHoverUntilLeave) { noClickHoverSuppressionRect = this.#getAnchorEl().getBoundingClientRect(); noClickHoverSuppressionAbortController?.abort(); noClickHoverSuppressionAbortController = new AbortController(); document.addEventListener("pointermove", event => { if (!(noClickHoverSuppressionRect && event.pointerType === "mouse") || pointerIsInsideRect(event, noClickHoverSuppressionRect)) { return; } noClickHoverSuppressionRect = null; noClickHoverSuppressionAbortController.abort(); noClickHoverSuppressionAbortController = null; }, { capture: true, passive: true, signal: noClickHoverSuppressionAbortController.signal }); }
      this.close();
    }, { capture: true });

    this._iconEl.addEventListener("click", e => {
      if (this.disabled) return;
      if (this.hasAttribute("no-click-open")) { return; }
      if (this.#suppressNextClick) {
        this.#suppressNextClick = false;
        e.stopPropagation(); e.preventDefault();
        return;
      }
      e.stopPropagation(); e.preventDefault();
      const alreadyOpen = this.hasAttribute("open");
      if (!alreadyOpen) {
        this.#openedByClick = true;
        this.open();
      } else if (this.#openedByClick) {
        this.close();
      } else {
        this.#openedByClick = true;
        this._tooltipEl.dataset.clickOpen = "";
      }
    });

    this._closeEl.addEventListener("click", e => {
      if (this.disabled) return;
      e.stopPropagation();
      this.close();
    });

    this.addEventListener("pointerenter", e => {
      if (this.disabled) return;
      if (e.pointerType && e.pointerType !== "mouse") return;
      if (this.#suppressHoverUntilLeave) { return; }
      if (noClickHoverSuppressionRect && pointerIsInsideRect(e, noClickHoverSuppressionRect)) { return; }
      if (noClickHoverSuppressionRect) { noClickHoverSuppressionRect = null; noClickHoverSuppressionAbortController?.abort(); noClickHoverSuppressionAbortController = null; }
      if (this.#openedByClick) return;
      this.#clearHoverTimer();
      this.#clearLeaveTimer();
      this.#hoverTimer = setTimeout(() => {
        if (this.isConnected && this.matches(":hover") && !this.#openedByClick && !this.disabled) this.open();
      }, this.hasAttribute("instant-hover") ? 0 : HOVER_DELAY_MS);
    });
    this.addEventListener("pointerleave", e => {
      if (this.disabled) return;
      if (e.pointerType && e.pointerType !== "mouse") return;
      this.#suppressHoverUntilLeave = false;
      this.#clearHoverTimer();
      if (this.#openedByClick) return;
      if (!this.hasAttribute("open")) return;
      if (this.hasAttribute("instant-hover")) { this.close(); return; }
      this.#scheduleLeaveClose();
    });

    this._tooltipEl.addEventListener("pointerenter", () => {
      if (this.disabled) return;
      this.#clearLeaveTimer();
    });
    this._tooltipEl.addEventListener("pointerleave", () => {
      if (this.disabled) return;
      if (this.#openedByClick) return;
      if (this.hasAttribute("instant-hover")) { this.close(); return; }
      this.#scheduleLeaveClose();
    });

    this.addEventListener("keydown", e => {
      if (this.disabled) return;
      if (this.hasAttribute("no-click-open")) return;
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        this.#openedByClick = true;
        this.toggle();
      }
    });
  }

  #bindOutside () {
    document.addEventListener("click",   this.#outsideClickHdl, { capture:true });
    document.addEventListener("keydown", this.#escHdl,          { capture:true });
  }
  #unbindOutside () {
    document.removeEventListener("click",   this.#outsideClickHdl, { capture:true });
    document.removeEventListener("keydown", this.#escHdl,          { capture:true });
  }
  #handleOutsideClick (e) {
    if (!this.hasAttribute("open")) return;
    if (this.contains(e.target) || this._tooltipEl.contains(e.target)) return;
    this.close();
  }
  #handleEsc (e) {
    if (e.key === "Escape" && this.hasAttribute("open")) {
      e.stopPropagation(); e.preventDefault(); this.close();
    }
  }
  #bindFocusLoss () {
    window.addEventListener("blur", this.#windowBlurHdl);
    document.addEventListener("visibilitychange", this.#visibilityHdl);
  }
  #unbindFocusLoss () {
    window.removeEventListener("blur", this.#windowBlurHdl);
    document.removeEventListener("visibilitychange", this.#visibilityHdl);
  }
  #handleWindowBlur () {
    this.#handleFocusLoss();
  }
  #handleVisibilityChange () {
    if (document.hidden) this.#handleFocusLoss();
  }
  #handleFocusLoss () {
    this.#clearHoverTimer();
    this.#clearLeaveTimer();
    this.close();
  }

  #scheduleLeaveClose() {
    this.#clearLeaveTimer();
    this.#leaveTimer = setTimeout(() => {
      this.close();
    }, HOVER_DELAY_CLOSE_MS);
  }
  #clearLeaveTimer() {
    if (this.#leaveTimer) {
      clearTimeout(this.#leaveTimer);
      this.#leaveTimer = null;
    }
  }

  #clearHoverTimer () {
    if (this.#hoverTimer) { clearTimeout(this.#hoverTimer); this.#hoverTimer = null; }
  }

  #handleIconPointerDown (e) {
    if (e.pointerType !== "touch") return;
    this.#touchPointerId = e.pointerId;
    this.#touchStartX = e.clientX;
    this.#touchStartY = e.clientY;
    this.#touchStartScrollX = window.scrollX;
    this.#touchStartScrollY = window.scrollY;
    this.#touchMoved = false;
    this.#suppressNextClick = false;
  }
  #handleIconPointerMove (e) {
    if (e.pointerType !== "touch" || this.#touchPointerId !== e.pointerId || this.#touchMoved) return;
    const movedX = Math.abs(e.clientX - this.#touchStartX);
    const movedY = Math.abs(e.clientY - this.#touchStartY);
    const scrollDeltaX = Math.abs(window.scrollX - this.#touchStartScrollX);
    const scrollDeltaY = Math.abs(window.scrollY - this.#touchStartScrollY);
    if (movedX > TOUCH_DRAG_THRESHOLD_PX || movedY > TOUCH_DRAG_THRESHOLD_PX || scrollDeltaX > 0 || scrollDeltaY > 0) {
      this.#touchMoved = true;
    }
  }
  #handleIconPointerEnd (e) {
    if (e.pointerType !== "touch" || this.#touchPointerId !== e.pointerId) return;
    const scrollDeltaX = Math.abs(window.scrollX - this.#touchStartScrollX);
    const scrollDeltaY = Math.abs(window.scrollY - this.#touchStartScrollY);
    this.#suppressNextClick = this.#touchMoved || scrollDeltaX > 0 || scrollDeltaY > 0;
    this.#touchPointerId = null;
    this.#touchMoved = false;
  }

  #syncAria () {
    const isOpen = this.hasAttribute("open");
    this.setAttribute("aria-expanded", String(isOpen));
    this._iconEl.setAttribute("aria-label", this.getAttribute("aria-label") || neo__("Info","Info"));
    this._tooltipEl.setAttribute("aria-hidden", String(!isOpen));

    if (this.disabled) {
      this.removeAttribute("role");
      this.removeAttribute("tabindex");
    } else if (this.hasAttribute("no-click-open")) {
      this.removeAttribute("role");
      this.removeAttribute("tabindex");
    } else {
      this.setAttribute("role","button");
      if (!this.hasAttribute("tabindex")) {
        this.setAttribute("tabindex","0");
      }
    }
  }

  #syncDisabledState () {
    if (this.disabled) {
      this.close();
      this.#clearHoverTimer();
      this.#clearLeaveTimer();
    }
  }

  #syncDefaultIconColor () {
    if (this.hasAttribute("default-icon-color")) {
      this.style.setProperty("--neo-global--icon-color", this.getAttribute("default-icon-color"));
    } else {
      this.style.removeProperty("--neo-global--icon-color");
    }
  }

  #projectContent () {
    this._contentEl.innerHTML = "";
    const frag = document.createDocumentFragment();
    Array.from(this.childNodes).forEach(node => {
      if (node.nodeType === Node.ELEMENT_NODE && node.hasAttribute("slot")) return;
      frag.appendChild(node.cloneNode(true));
    });
    this._contentEl.appendChild(frag);
    if (this.hasAttribute("open")) this.#reposition();
  }

  #bindContentObserver () {
    if (this.#contentObserver) return;
    this.#contentObserver = new MutationObserver(() => this.#projectContent());
    this.#contentObserver.observe(this, { childList:true, subtree:true, characterData:true });
  }

  #unbindContentObserver () {
    if (!this.#contentObserver) return;
    this.#contentObserver.disconnect();
    this.#contentObserver = null;
  }

  #bindViewport () {
    window.addEventListener("scroll", this.#repositionHdl, true);
    window.addEventListener("resize", this.#repositionHdl);
  }
  #unbindViewport () {
    window.removeEventListener("scroll", this.#repositionHdl, true);
    window.removeEventListener("resize", this.#repositionHdl);
  }

  #reposition () {
    this._tooltipEl.style.visibility = "hidden";
    this._tooltipEl.style.display    = "block";

    const iconRect    = this.#getAnchorEl().getBoundingClientRect();
    const tooltipRect = this._tooltipEl.getBoundingClientRect();
    const vpW         = window.innerWidth;
    const vpH         = window.innerHeight;

    const spaceBelow  = vpH - iconRect.bottom;
    const spaceAbove  = iconRect.top;
    const placement   =
      (spaceBelow < tooltipRect.height + MARGIN_PX && spaceAbove > spaceBelow)
        ? "top"
        : "bottom";
    let left = iconRect.left + iconRect.width / 2 - tooltipRect.width / 2;
    left = Math.max(4, Math.min(left, vpW - tooltipRect.width - 4));

    let top;
    if (placement === "bottom") {
      top = iconRect.bottom + MARGIN_PX;
    } else {
      top = iconRect.top - tooltipRect.height - MARGIN_PX;
      if (top < 4) top = 4;
    }
    top += Number(this.getAttribute("tooltip-offset-y") || 0);

    this._tooltipEl.style.left = `${Math.round(left)}px`;
    this._tooltipEl.style.setProperty("--neo-global--tooltip-arrow-left", `${Math.round(Math.max(14, Math.min(iconRect.left + iconRect.width / 2 - left, tooltipRect.width - 14)))}px`);
    this._tooltipEl.style.top  = `${Math.round(top)}px`;
    this._tooltipEl.dataset.placement = placement;

    this._tooltipEl.style.visibility = "";
    this._tooltipEl.style.display    = "";
  }

  #getAnchorEl () {
    return this._iconSlotEl.assignedElements({ flatten: true })[0] || this._iconEl;
  }
}
