import { jsVar } from "./_global--enqueue-loader.js";
import { observeEditorImages } from "./neo-image-cachebust--helper.js";

observeEditorImages(document.documentElement, "img", { queryKey: "neo-image-cachebust--divi-editor", cachebustValue: jsVar("neoImageCachebustLastChangeDateDiviEditor") });
