import { delay, observeOnce } from "./_global--observer.js";
import { neoWarn } from "./_global--log.js";

export async function interfaceRunNeoDrawPlaygroundDemo20260612(openAnimationDialog) {
    const demoImageButton = await Promise.race([observeOnce(".neo-draw--media-library-inline-edit-button-replaced[data-neo-draw--img-url$=\"/demo-image-colorful-neodraw.svg\" i]"), delay(3).then(() => null)]);
    if (!demoImageButton) { neoWarn("neoDraw playground demo image not found: demo-image-colorful-neodraw.svg"); (await observeOnce(".neo-draw--pb-media-library-create-button")).click(); }
    if (demoImageButton) { demoImageButton.click(); }
    if (!openAnimationDialog) { return; }
    const editorIframe = await observeOnce(".neo-draw--editor-iframe-loaded");
    const animationButton = await Promise.race([observeOnce(".neo-draw--animation-button", undefined, { domRoot: editorIframe.contentDocument }), delay(3).then(() => null)]);
    if (animationButton) { animationButton.click(); return; }
    neoWarn("neoDraw playground animation button not found for animation demo");
}
