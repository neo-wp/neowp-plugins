import { observeOnce } from "./_global--observer.js";
import { neo__ } from "./_global--translation.js";
import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { isNeoDrawImage } from "./_global-image-metadata.js";
import { removeAllQueryParams } from "./_global--url-helper.js";

observeOnce('[data-control="image"]', (imageControl) => {
    const hint = neo__("Everything as usual, additionally with neoDraw.", "Alles wie gewohnt, zusätzlich mit neoDraw.");
    const hintNode = new DomNodeHelper(`<div style="color: var(--builder-color-description);">${hint}</div>`).getNode();
    imageControl.children[0].after(hintNode);
});

observeOnce('[data-control="image"]>.image-wrapper', async (imageWrapper) => {
    while (!imageWrapper.querySelector("img")?.getAttribute("src")) { await new Promise(requestAnimationFrame); }
    const imgUrl = imageWrapper.querySelector("img").getAttribute("src");
    if (!removeAllQueryParams(imgUrl).endsWith(".svg")) { return; }
    const svgContent = await fetch(imgUrl).then(response => response.text());
    if (!isNeoDrawImage(svgContent)) { return; }
    const hint = neo__("Remove to change", "Entfernen zum Ändern");
    const hintNode = new DomNodeHelper(`<div style="position: absolute; bottom: 0; right: 0; padding: 8px; border-radius: 4px 0 0 0; background: var(--bricks-tooltip-bg); color: var(--bricks-tooltip-text); cursor: pointer;">${hint}</div>`).getNode();
    hintNode.addEventListener("click", () => imageWrapper.querySelector(".delete")?.click());
    imageWrapper.append(hintNode);
});
