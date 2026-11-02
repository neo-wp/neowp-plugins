<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("wp_enqueue_media:11", function () {
    if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }
    \NeoDraw\NeoGlobal\enqueue_js("neo-draw--pb-wp-media-selector.js", dependencies: ["media-editor"]);
    interface_draw_editor_dialog_init_20250302();
});

\NeoDraw\NeoGlobal\add_filter_hook("wp_prepare_attachment_for_js", function ($response, $attachment, $meta) {
    $response["is_neodraw"] = \NeoDraw\NeoGlobal\is_neodraw_image_post($attachment);
    if ($response["is_neodraw"]) {
        $neo_meta = \NeoDraw\NeoGlobal\get_image_metadata_by_post_id($attachment->ID);
        if ($neo_meta === false) { \NeoDraw\NeoGlobal\global_warn_with_module_name("neo-draw", "Broken SVG metadata of image ID " . $attachment->ID); return $response; }
        $response["neo_draw__last_modified"] = $neo_meta ? $neo_meta["lastModified"] : "";
    }
    return $response;
});
