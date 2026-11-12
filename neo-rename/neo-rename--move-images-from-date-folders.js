import { observeClick } from "./_global--observer.js";
import { reloadPage } from "./_global-reload-page.js";
import { openRenameDialog } from "./neo-rename--dialog.js";

observeClick("#neo-rename--move-images-from-date-folders", async (button) => {
    let didRename = false;
    await openRenameDialog({ filterInputText: "", inputMode: "remove-subfolder", onUpdateCallback: () => { didRename = true; } });
    if (didRename) { reloadPage(); }
});
