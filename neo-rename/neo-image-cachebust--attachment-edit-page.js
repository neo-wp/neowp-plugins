import { jsVar } from "./_global--enqueue-loader.js";
import { observeEditorImages } from "./neo-image-cachebust--helper.js";

observeEditorImages(document.documentElement, ".wp_attachment_image img.thumbnail", { queryKey: "neo-image-cachebust--attachment-edit-page", cachebustValue: jsVar("neoImageCachebustLastChangeDateAttachmentEditPage") });
