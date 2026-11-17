import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { neo__ } from "./_global--translation.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { jsVar } from "./_global--enqueue-loader.js";

document.getElementById("file-form").appendChild(new DomNodeHelper(`<div style="display:flex;align-items:center;">
    <img style="width:16px;aspect-ratio:1;margin-right:4px;" src="${pluginUrl()}/img/_global--mini-icon.svg" alt="">
    <span>${neo__("Use the ", "Verwende die ")}<a href="${jsVar("neoDrawUploadOldHintLibraryUrl")}">${neo__("neoLibrary", "neoLibrary")}</a>${neo__(" to upload neoDraw SVG files correctly.", ", um neoDraw SVG-Dateien korrekt hochzuladen.")}</span>
</div>`).getNode());
