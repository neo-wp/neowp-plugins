<?php
namespace NeoRename\NeoRename; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/rename-dialog-media-list", "GET", fn () => \NeoRename\NeoGlobal\current_user_can__neo_rename(), function () {
    $query = new \WP_Query(["post_type" => "attachment", "post_status" => "inherit", "posts_per_page" => -1, "orderby" => "title", "order" => "ASC"]);
    $media_items = [];
    foreach ($query->posts as $image) {
        $img_url = wp_get_attachment_url($image->ID);
        $image_path_rel = str_replace(\NeoRename\NeoGlobal\uploads_dir() . "/", "", get_attached_file($image->ID));
        $image_metadata = \NeoRename\NeoGlobal\post_meta($image->ID, "_wp_attachment_metadata"); $original_image_filename = is_array($image_metadata) ? ($image_metadata["original_image"] ?? "") : "";
        $item = [
            "id"     => $image->ID,
            "imgUrl" => \NeoRename\NeoGlobal\percent_encode_invalid_utf8_url_bytes($img_url),
            "title"  => $image->post_title,
            "slug"   => $image->post_name,
            "uploadDate" => $image->post_date_gmt,
            "mimeType" => get_post_mime_type($image->ID) ?: "",
            "isNeoDraw" => \NeoRename\NeoGlobal\is_neodraw_image_post($image),
            "altText" => \NeoRename\NeoGlobal\post_meta($image->ID, "_wp_attachment_image_alt") ?: "",
            "originalPathRel" => $original_image_filename !== "" ? \NeoRename\NeoGlobal\percent_encode_invalid_utf8_url_bytes(\NeoRename\NeoGlobal\path_join_rel(dirname($image_path_rel) === "." ? "" : dirname($image_path_rel), $original_image_filename)) : null,
        ];

        [$undo_data, $interface_ok] = \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoRenameUndo\interface_get_undo_data_20250915')($image->ID); if (!$interface_ok) { $undo_data = null; }
        if (isset($undo_data["pathRel"])) { $undo_data["pathRel"] = \NeoRename\NeoGlobal\percent_encode_invalid_utf8_url_bytes($undo_data["pathRel"]); }
        if ($undo_data !== null) { $item["undoData"] = $undo_data; }

        $media_items[] = $item;
    }

    $media_items = \NeoRename\NeoGlobal\array_filter_better($media_items, fn ($item) => \NeoRename\NeoGlobal\is_url_in_uploads_folder($item["imgUrl"]));
    $media_items = array_map(fn ($item) => array_merge($item, ["pathRel" => \NeoRename\NeoGlobal\make_internal_url_relative_to_uploads($item["imgUrl"])]), $media_items);
    return $media_items;
});

\NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/rename-dialog-date-upload-folder-setting-enabled", "GET", fn () => \NeoRename\NeoGlobal\current_user_can__neo_rename(), function () {
    return ["value" => get_option("uploads_use_yearmonth_folders") === "1"];
});

\NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/rename-dialog-disable-date-upload-folder-setting", "POST", fn () => \NeoRename\NeoGlobal\current_user_can__neo_rename__settings(), function () {
    \NeoRename\NeoGlobal\global_log_with_module_name("neo-rename", "Disabling 'uploads in year/month folders' setting for neoRename user request.");
    update_option("uploads_use_yearmonth_folders", "0");
    return "OKAY";
});

\NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/rename-dialog-log-state", "POST", fn () => \NeoRename\NeoGlobal\current_user_can__neo_rename(), function ($get_param) {
    \NeoRename\NeoGlobal\global_log_with_module_name("neo-rename", "Rename button clicked: " . \NeoRename\NeoGlobal\json_encode_better($get_param("state")));
    return "OKAY";
});
