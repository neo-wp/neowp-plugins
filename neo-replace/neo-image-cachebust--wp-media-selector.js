import { jsVar } from "./_global--enqueue-loader.js";
import { observeEditorImages } from "./neo-image-cachebust--helper.js";

observeEditorImages(document.documentElement, ".attachments-browser .attachment-preview img, .attachment-details .details-image", { queryKey: "neo-image-cachebust--wp-media-selector", cachebustValue: jsVar("neoImageCachebustLastChangeDateWpMediaSelector") });
