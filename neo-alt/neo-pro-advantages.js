import { jsVar } from "./_global--enqueue-loader.js";
import { observeOnce } from "./_global--observer.js";
import { loadOrHideImage } from "./_global-load-or-hide-image.js";

observeOnce("#neo-pro-advantages--image", async (imgNode) => { loadOrHideImage(imgNode, jsVar("neoProAdvantagesImageUrl")); });
