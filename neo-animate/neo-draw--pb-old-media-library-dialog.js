import { neoLoadInterfaceFunc } from "./_global--interface.js";

let savedImgUrl = "";
(async () => new (await neoLoadInterfaceFunc("neo-animate", "neo-draw--editor-dialog.js", "InterfaceEditorDialog20260826"))().fullSize().open().on("save", ({ imgUrl }) => { savedImgUrl = imgUrl; }).on("close", () => { if (savedImgUrl) { parent.window.send_to_editor(`<img src="${savedImgUrl}" />`); parent.window.tb_remove(); } }))();
