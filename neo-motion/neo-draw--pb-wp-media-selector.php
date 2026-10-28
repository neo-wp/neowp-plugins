<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("wp_enqueue_media:11", function () {
    if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; }
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--pb-wp-media-selector.js", dependencies: ["media-editor"]);
    interface_draw_editor_dialog_init_20250302();
});

\NeoMotion\NeoGlobal\add_filter_hook("wp_prepare_attachment_for_js", function ($response, $attachment, $meta) {
    $response["is_neodraw"] = \NeoMotion\NeoGlobal\is_neodraw_image_post($attachment);
    if ($response["is_neodraw"]) {
        $neo_meta = \NeoMotion\NeoGlobal\get_image_metadata_by_post_id($attachment->ID);
        if ($neo_meta === false) { \NeoMotion\NeoGlobal\global_warn_with_module_name("neo-draw", "Broken SVG metadata of image ID " . $attachment->ID); return $response; }
        $response["neo_draw__last_modified"] = $neo_meta ? $neo_meta["lastModified"] : "";
    }
    return $response;
});
