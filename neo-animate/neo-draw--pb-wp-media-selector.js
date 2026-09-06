const wpMediaSelectorModulePromise = import("./neo-draw--pb-wp-media-selector-module.js");

function neoExtendMediaFrameSelect() {
    if (!wp?.media?.view?.MediaFrame?.Select) { requestAnimationFrame(neoExtendMediaFrameSelect); return; }
    wp.media.view.MediaFrame.Select = wp.media.view.MediaFrame.Select.extend({
        open: async function () {
            const { wpMediaSelectorOpen } = await wpMediaSelectorModulePromise;
            wpMediaSelectorOpen.apply(this, arguments);
        }
    });
}
neoExtendMediaFrameSelect();
