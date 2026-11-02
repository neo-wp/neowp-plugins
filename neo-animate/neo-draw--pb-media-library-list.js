import { DomNodeHelper, escapeCssSelectorString } from "./_global--dom-node-helper.js";
import { observeOnce } from "./_global--observer.js";
import { addCacheBust } from "./_global--url-helper.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { reloadPage } from "./_global-reload-page.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";

let wasSaved = false;

function updateImage({ imgUrl }) {
    wasSaved = true;

    const img = document.querySelector(`img[src^="${escapeCssSelectorString(imgUrl)}"]`);
    if (img) {
        img.setAttribute("src", addCacheBust(imgUrl));
    }
}

observeOnce(".neo-draw--media-library-inline-edit-button:not(.neo-draw--media-library-inline-edit-button-replaced)", async (button) => {
    const imgUrl = button.getAttribute("data-neo-draw--img-url");
    let savedImgUrl = null;
    const inlineEditDialog = new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))()
        .imgUrl(imgUrl)
        .on("save", (details) => { savedImgUrl = details.imgUrl; updateImage(details); })
        .on("close", () => {
            const isNeoDraw = button.getAttribute("data-neo-draw--is-neodraw") === "true";
            if (wasSaved && (!isNeoDraw || savedImgUrl !== imgUrl)) {
                reloadPage();
            }
            wasSaved = false; savedImgUrl = null;
        });
    const inlineEditButton = new DomNodeHelper(`<button type="button">${button.innerHTML}</button>`)
        .withClasses("button-link", "edit-attachment")
        .withClasses("neo-draw--media-library-inline-edit-button-replaced")
        .withClasses(...button.classList)
        .on("click", () => inlineEditDialog.open())
        .getNode();
    for (const attribute of button.attributes) {
        if (attribute.name === "class") { continue; }
        inlineEditButton.setAttribute(attribute.name, attribute.value);
    }
    button.replaceWith(inlineEditButton);
});

observeOnce("table.media tr", async (tr) => {
    const isNeodrawImage = Boolean(tr.querySelector("[data-neo-draw--is-neodraw='true']"));
    if (!isNeodrawImage) { return; }
    const logo = document.createElement("img");
    logo.src = `${pluginUrl()}/img/_global--mini-icon.svg`;
    logo.alt = "neoDraw";
    logo.className = "neo-draw--media-title-logo";
    const mediaIcon = tr.querySelector(".media-icon.image-icon");

    mediaIcon.parentNode.insertBefore(logo, mediaIcon.nextSibling);
});

observeOnce("table.media tr", async (tr) => {
    const isNeodrawImage = Boolean(tr.querySelector("[data-neo-draw--is-neodraw='true']"));
    if (isNeodrawImage) {
        tr.classList.add("neo-draw--media-row");
    } else if (tr.querySelector(".image-icon")) {
        tr.classList.add("neo-draw--media-row");
        tr.classList.add("neo-draw--media-insertable");
    }
});

(await neoLoadInterfaceFunc("neo-animate", "neo-tutorial.js", "interfaceShowTutorialArrowSuppressErrorPopup20260410"))(".neo-draw--media-library-inline-edit-button-replaced", "top", "new", true);
