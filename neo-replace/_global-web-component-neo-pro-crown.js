import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { pricingUrl } from "./_global-pricing-url.js";

export default class NeoProCrown extends HTMLElement {
    constructor() {
        super();

        this.attachShadow({ mode: "open" });

        this.shadowRoot.innerHTML = `
            <style>
                :host { display: block; line-height: 1; }
                a:hover { filter: brightness(1.1); }
                a { display: block; text-decoration: none; line-height: 0; }
                img { width: 100%; height: 100%; object-fit: contain; max-width: 24px; max-height: 24px; }
            </style>
            <a href="${pricingUrl()}" target="_blank" rel="noopener" title="${neo__('Get neoPro now to unlock this feature', 'Jetzt neoPro holen, um dieses Feature freizuschalten')}">
                <img src="${pluginUrl()}/img/plugin-neo-all-icon-crown.svg" alt="${neo__('Pro', 'Pro')}">
            </a>`;
    }
}
