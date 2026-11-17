import { jsVar } from "./_global--enqueue-loader.js";
import { addEventListenerWithInitialCall, observeOnce } from "./_global--observer.js";
import { observeEditorImages } from "./neo-image-cachebust--helper.js";

observeOnce("#content_ifr", (editorIframe) => {
    addEventListenerWithInitialCall(editorIframe, "load", () => {
        if (!editorIframe.contentDocument?.documentElement) { return; }
        observeEditorImages(editorIframe.contentDocument.documentElement, "img", { queryKey: "neo-image-cachebust--classic-editor", cachebustValue: jsVar("neoImageCachebustLastChangeDateClassicEditor") });
    });
});
