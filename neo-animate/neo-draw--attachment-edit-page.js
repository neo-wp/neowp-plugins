import { DomNodeHelper } from "./_global--dom-node-helper.js";
import { observeOnce } from "./_global--observer.js";
import { pluginUrl } from "./_global-plugin-and-uploads-url.js";
import { reloadPage } from "./_global-reload-page.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";

observeOnce("#misc-publishing-actions:not(.neo-draw--attachment-edit-page-button-added)", async (publishingActions) => {
    const attachmentUrlInput = document.querySelector("#attachment_url");
    if (!attachmentUrlInput?.value) { return; }
    let wasSaved = false;
    const editorDialog = new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))().imgUrl(attachmentUrlInput.value).on("save", () => { wasSaved = true; }).on("close", () => { if (wasSaved) { reloadPage(); } });
    const button = new DomNodeHelper(`<button type="button" class="button button-secondary neo-draw--attachment-edit-page-button" style="display: inline-flex; align-items: center; gap: 0.35em;"><img alt="" src="${pluginUrl()}/img/neo-draw--edit-icon.svg" style="height: 1.2em; width: 1.2em;">neoDraw</button>`).on("click", () => editorDialog.open()).getNode();
    const buttonContainer = new DomNodeHelper(`<div class="misc-pub-section neo-draw--attachment-edit-page-button-section"></div>`).getNode();
    buttonContainer.append(button);
    publishingActions.classList.add("neo-draw--attachment-edit-page-button-added");
    (publishingActions.querySelector(".misc-pub-dimensions") ?? publishingActions.lastElementChild)?.after(buttonContainer);
});
