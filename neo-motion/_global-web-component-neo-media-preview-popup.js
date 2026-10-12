import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { getFileType } from "./_global-media-file-type.js";
import { addCacheBust, addQueryParam, fitProtocolToFetchImgUrl, removeQueryParam } from "./_global--url-helper.js";
import { isSafari } from "./_global--detect-safari.js";

export default class NeoMediaPreviewPopup extends HTMLElement {
  static get observedAttributes() { return ['open', 'src']; }

  constructor() {
    super();
    const shadow = this.attachShadow({ mode: 'open' });

    this._style = document.createElement('style');
    this._style.textContent = `
:host {
    position: fixed; inset: 0;
    z-index: 1000000;
    display: none;
    justify-content: center; align-items: center;
    backdrop-filter: blur(5px);
    background-color: rgba(0, 0, 0, 0);
    animation: dialog-background 0.2s ease-out both;
    --dialog-padding: 2.5vmin;
    padding: var(--dialog-padding); box-sizing: border-box;
}
:host([open]) { display: flex; }

@keyframes dialog-background {
    from { background-color: rgba(0, 0, 0, 0); }
    to   { background-color: rgba(0, 0, 0, 0.5); }
}

.dialog-box {
    position: relative; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,.5);
    width: 100%; height: 100%; max-width: 100%; max-height: 100%; border-radius: 10px;
    overflow: hidden; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    width: fit-content; height: fit-content; min-width: 100px; min-height: 100px;
    overflow: visible !important;
    transform: scale(0.2); animation: dialog-box-scale 0.2s ease-out both;
}
@keyframes dialog-box-scale { from { transform: scale(0.2); } to { transform: scale(1); } }

.close-button {
    margin: 0; padding: 0; border: none; cursor: pointer;
    position: absolute; top: 0; right: 0; width: 28px; height: 28px; transform: translate(50%, -50%);
    background: #fff; border-radius: 50%; filter: drop-shadow(1px 1px 4px #000);
    display: flex; align-items: center; justify-content: center;
    user-select: none;
}
.close-button > img { width: 50%; height: 50%; }

.loading-spinner {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 50px; height: 50px; border-radius: 50%;
    border: 6px solid rgba(0,0,0,.2); border-top-color: rgba(0,0,0,.8);
    animation: dialog-loading-spinner-rotate 1s linear infinite;
}
@keyframes dialog-loading-spinner-rotate {
    from { transform: translate(-50%, -50%) rotate(0deg); }
    to   { transform: translate(-50%, -50%) rotate(360deg); }
}

.dialog-box:not(:has(.loading-spinner)) { min-width: 40px; min-height: 40px; }
.dialog-box :is(img, video, object) { display: block; max-width: 100%; border-radius: inherit; }
.dialog-box :is(img, video, object) { max-height: calc(100vh - 2 * var(--dialog-padding)); }
.dialog-box :is(img, video, object) { user-select: none; }
.dialog-box object { width: 100vw; height: 100vh; }
    `;
    shadow.appendChild(this._style);

    this._onEsc = this._onEsc.bind(this);
  }

  attributeChangedCallback(name) {
    if (name === 'open') {
      if (this.hasAttribute('open')) { this._teardown(); this._render(); } else { this._teardown(); }
    }
    if (name === 'src' && this.hasAttribute('open')) { this._render(); }
  }

  open(url) { if (url) this.setAttribute('src', url); this.setAttribute('open', ''); }
  close()   { this.removeAttribute('open'); }

  connectedCallback()  { if (this.hasAttribute('open')) this._render(); }
  disconnectedCallback(){ this._teardown(); }

  _render() {
    const rawSrc = this.getAttribute('src') || '';
    const kind   = this._detectKind(rawSrc);
    const src    = this._prepareSrc(rawSrc, kind);

    this._teardown();

    const box = document.createElement('div');
    box.className = 'dialog-box';
    this._box = box;

    const closeBtn = document.createElement('button');
    closeBtn.className = 'close-button';
    closeBtn.innerHTML = `<img src="${pluginUrl()}/img/_global-close-icon.svg" alt="${neo__('Close','Schließen')}" />`;
    closeBtn.setAttribute('fetchpriority', 'high');
    this._closeBtn = closeBtn;
    box.appendChild(closeBtn);

    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    this._spinner = spinner;
    box.appendChild(spinner);

    let mediaEl;
    if (kind === 'image' || kind === 'other') {
      const img = new Image();
      img.alt = neo__('Preview', 'Vorschau');
      img.src = src;
      img.setAttribute('fetchpriority', 'high');
      mediaEl = img;
      box.appendChild(img);
      img.addEventListener('load', () => this._spinner?.remove(), { once: true });
      img.addEventListener('error', () => this._spinner?.remove(), { once: true });
    } else if (kind === 'video') {
      const video = document.createElement('video');
      video.src = src; video.controls = true; video.autoplay = true; video.playsInline = true;
      mediaEl = video; box.appendChild(video);
      const onReady = () => { this._spinner?.remove(); video.play().catch(() => {}); };
      video.addEventListener('loadeddata', onReady, { once: true });
      video.addEventListener('error', () => this._spinner?.remove(), { once: true });
    } else if (kind === 'audio') {
      const audio = document.createElement('audio');
      audio.src = src; audio.controls = true; audio.autoplay = true;
      mediaEl = audio; box.appendChild(audio);
      const onReady = () => { this._spinner?.remove(); audio.play().catch(() => {}); };
      audio.addEventListener('loadeddata', onReady, { once: true });
      audio.addEventListener('error', () => this._spinner?.remove(), { once: true });
    } else if (kind === 'pdf' || kind === 'txt') {
      const obj = document.createElement('object');
      obj.data = src;
      mediaEl = obj; box.appendChild(obj);
      obj.addEventListener('load',  () => this._spinner?.remove(), { once: true });
      obj.addEventListener('error', () => this._spinner?.remove(), { once: true });
    } else if (kind === 'zip') {
        const img = new Image();
        img.alt = neo__('Preview', 'Vorschau');
        img.src = `${pluginUrl()}/img/_global-placeholder-zip-icon.svg`;
        img.setAttribute('fetchpriority', 'high');
        mediaEl = img;
        box.appendChild(img);
        img.addEventListener('load', () => this._spinner?.remove(), { once: true });
        img.addEventListener('error', () => this._spinner?.remove(), { once: true });
    }

    this.addEventListener('click', this._onBackdropClick, { once: true });
    this.addEventListener('pointerup', this._stopPointerEvent); this.addEventListener('pointerdown', this._stopPointerEvent);
    closeBtn.addEventListener('click', () => this.close());
    window.addEventListener('keydown', this._onEsc);

    this.shadowRoot.appendChild(box);

    this._focusMedia(mediaEl, kind);
  }

  _teardown() {
    window.removeEventListener('keydown', this._onEsc);
    this.removeEventListener('click', this._onBackdropClick);
    this.removeEventListener('pointerup', this._stopPointerEvent);
    this.removeEventListener('pointerdown', this._stopPointerEvent);
    if (this._box && this._box.isConnected) {
      const media = this._box.querySelector('video, audio');
      if (media) { try { media.pause(); } catch {} media.removeAttribute('src'); media.load?.(); }
      this._box.remove();
    }
    this._box = null;
    this._spinner = null;
    this._closeBtn = null;
  }

  _onBackdropClick = (ev) => { ev.stopPropagation(); this.close(); };
  _onEsc(e) { if (e.key === 'Escape') { e.stopImmediatePropagation(); this.close(); } }
  _stopPointerEvent = (e) => { e.stopPropagation(); };

  _focusMedia(mediaEl, kind) {
    if (!mediaEl || (kind !== 'video' && kind !== 'audio')) return;

    mediaEl.tabIndex = -1;

    requestAnimationFrame(() => mediaEl.focus({ preventScroll: true }));
  }

  _detectKind(url) {
    const clean = (url || '').split('?')[0].split('#')[0].toLowerCase();
    return getFileType(clean);
  }

  _prepareSrc(url, kind) {
    if (!url) return url;
    if (url.startsWith('blob:') || url.startsWith('data:')) return url;

    let u = fitProtocolToFetchImgUrl(url);

    const isSvg = /\.svg(?:[?#]|$)/i.test(u) || (kind === 'image' && /svg/i.test(u.split('.').pop()?.split('?')[0] ?? ''));
    if (isSafari() && isSvg) {
      const t = Date.now();
      u = addCacheBust(removeQueryParam(u, "cachebust"), t);
      u = addQueryParam(removeQueryParam(u, "cachebust2"), "cachebust2", t + 1);
    }
    return u;
  }
}
