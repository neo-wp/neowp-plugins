import { observeOnce } from "./_global--observer.js";
import { integrateWpAltTextField } from "./neo-alt--wp-alt-text-field.js";

observeOnce("#attachment_alt", (inputNode) => {
    integrateWpAltTextField({ inputNode,
        getImageUrl: () => document.querySelector("#attachment_url")?.value || document.querySelector(".wp_attachment_image img")?.src || "",
        getImageTitle: () => document.querySelector("#title")?.value || null,
    });
});
