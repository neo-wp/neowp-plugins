<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_filter_hook("wp_update_attachment_metadata", "wp_generate_attachment_metadata", function ($data, $attachment_id) {
    if (isset($data["width"]) && isset($data["height"])) { return $data; }
    if (\NeoDraw\NeoGlobal\is_neodraw_image_post(get_post($attachment_id))) {
        $neodraw_metadata = \NeoDraw\NeoGlobal\get_image_metadata_by_post_id($attachment_id);
        if ($neodraw_metadata === false) { \NeoDraw\NeoGlobal\global_warn_with_module_name("neo-draw", "Broken SVG metadata of image ID $attachment_id"); return $data; }
        $data["width"] = $neodraw_metadata["width"]; $data["height"] = $neodraw_metadata["height"];
    }
    return $data;
});
