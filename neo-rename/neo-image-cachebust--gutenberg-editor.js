import { jsVar } from "./_global--enqueue-loader.js";
import { addEventListenerWithInitialCall, observeOnce } from "./_global--observer.js";
import { observeEditorImages } from "./neo-image-cachebust--helper.js";

observeEditorImages(document.documentElement, "#editor.block-editor__container img", { queryKey: "neo-image-cachebust--gutenberg-editor", cachebustValue: jsVar("neoImageCachebustLastChangeDateGutenbergEditor") });
observeOnce("iframe[name=\"editor-canvas\"]", (editorIframe) => {
    addEventListenerWithInitialCall(editorIframe, "load", () => {
        if (!editorIframe.contentDocument?.documentElement) { return; }
        observeEditorImages(editorIframe.contentDocument.documentElement, "img", { queryKey: "neo-image-cachebust--gutenberg-editor", cachebustValue: jsVar("neoImageCachebustLastChangeDateGutenbergEditor") });
    });
});
