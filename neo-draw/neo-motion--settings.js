import { observeClick } from "./_global--observer.js";
import { jsVar } from "./_global--enqueue-loader.js";
import { neoLoadInterfaceFunc } from "./_global--interface.js";
import { getQueryParam, removeQueryParam } from "./_global--url-helper.js";

async function openNeoMotionStarterTemplate(buttonNode = null) {
    if (buttonNode) { buttonNode.disabled = true; }
    let wasSaved = false;
    new (await neoLoadInterfaceFunc("neo-draw", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))()
        .imgUrl(jsVar("neoMotionStarterImageUrl"))
        .forceNew()
        .defaultFilename("neo-motion.svg")
        .on("save", () => { wasSaved = true; })
        .on("close", () => { if (buttonNode) { buttonNode.disabled = false; } if (wasSaved) { location.href = jsVar("neoMotionMediaLibraryUrl"); } })
        .open();
}

observeClick(".neo-motion--create-starter-button", async (buttonNode) => {
    await openNeoMotionStarterTemplate(buttonNode);
});

if (getQueryParam(location.href, "neo-motion--create") === "true") {
    history.replaceState({}, "", removeQueryParam(location.href, "neo-motion--create"));
    await openNeoMotionStarterTemplate();
}
