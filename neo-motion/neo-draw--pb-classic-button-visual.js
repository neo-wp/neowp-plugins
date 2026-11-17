tinymce.PluginManager.add("neo_draw__mce_button", function (editor, url) {
    editor.on("init", function() {
        document.querySelector("head").appendChild(editor.dom.create("link", { rel: "stylesheet", href: url + "/neo-draw--pb-classic-button.css" }));
    });

    editor.addButton("neo_draw__mce_button", {
        tooltip: "neoDraw",
        icon: "neo_draw",
        onclick: async () => {
            const selectedNode = tinymce.activeEditor.selection.getNode();
            const selectedNodeIsImgNode = selectedNode?.nodeName === 'IMG';

            let savedImgUrl = "";
            const onClose = async () => {
                if (!savedImgUrl) { return; }
                const { escapeCssSelectorString, escapeHtml } = await import("./_global--dom-node-helper.js");
                if (selectedNodeIsImgNode) {
                    tinymce.activeEditor.dom.setAttrib(selectedNode, 'src', savedImgUrl);
                } else {
                    editor.insertContent(`<img src="${escapeHtml(savedImgUrl)}" />`);
                }

                let classicEditorIframeDocument = document.getElementById("content_ifr")?.contentWindow?.document;
                if (!classicEditorIframeDocument) {
                    classicEditorIframeDocument = document;
                }
                for (const otherImg of classicEditorIframeDocument.querySelectorAll(`img[src$="${escapeCssSelectorString(savedImgUrl.replace("http://", "").replace("https://", ""))}"]`)) {
                    otherImg.setAttribute("src", otherImg.getAttribute("src"));
                }
            };

            const oldImgUrl = selectedNodeIsImgNode ? selectedNode.src : '';
            new (await import("./neo-draw--editor-dialog.js")).InterfaceEditorDialog20260826().imgUrl(oldImgUrl).open().on("save", ({ imgUrl }) => { savedImgUrl = imgUrl; }).on("close", onClose);
        }
    });
});
