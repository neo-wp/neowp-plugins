<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\frontend_image_hook_register_callback(function ($get_attr, $set_attr, $image_file_path) {
    if (!\NeoAnimate\NeoGlobal\is_neodraw_image_path($image_file_path)) { return; }

    $neodraw_metadata = \NeoAnimate\NeoGlobal\get_image_metadata_by_path($image_file_path);
    if ($neodraw_metadata === false) { return; }
    if (!isset($neodraw_metadata["isLinked"])) { \NeoAnimate\NeoGlobal\global_warn_with_module_name("neo-draw", "Unexpected error: neoDraw metadata of $image_file_path does not contain the 'isLinked' key.");   return; }
    $query_parameters = [
        "neo-draw"         => "true",
        "neo-draw--linked" => $neodraw_metadata["isLinked"] ? "true" : "false",
    ];

    $set_attr("src", \NeoAnimate\NeoGlobal\add_or_update_query_params($get_attr("src"), $query_parameters));
});
