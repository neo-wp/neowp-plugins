<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function update_attachment_metadata($attachment_id, $filepath = null) {
    $filepath ??= get_attached_file($attachment_id);

    require_once(ABSPATH . "wp-admin/includes/image.php"); /* This authenticated REST operation creates WordPress attachment metadata. #suppressLinterWporgDirectoryConstantCheck #suppressLinterWPorgAutoCoreIncludeCheck */

    $generated_metadata = wp_generate_attachment_metadata($attachment_id, $filepath);
    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Update neoDraw attachment metadata: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["attachmentId" => $attachment_id, "filepath" => $filepath, "metadataFile" => $generated_metadata["file"] ?? null, "width" => $generated_metadata["width"] ?? null, "height" => $generated_metadata["height"] ?? null])));
    wp_update_attachment_metadata($attachment_id, $generated_metadata);
    $timestamp = time();
    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Update neoDraw attachment modified date: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["attachmentId" => $attachment_id, "postModified" => \NeoAnimate\NeoGlobal\wp_date_string(timestamp: $timestamp), "postModifiedGmt" => \NeoAnimate\NeoGlobal\utc_date_string(timestamp: $timestamp)])));
    wp_update_post(["ID" => $attachment_id, "post_modified" => \NeoAnimate\NeoGlobal\wp_date_string(timestamp: $timestamp), "post_modified_gmt" => \NeoAnimate\NeoGlobal\utc_date_string(timestamp: $timestamp)]);
}

function add_svg_to_wp_media($svg_content, $post_parent_id, $upload_dir, $filename, $title, $slug, $date_timestamp = null) {
    if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return new \WP_Error("rest_forbidden", \NeoAnimate\NeoGlobal\neo__("No permissions to create neoDraw images.", "Keine Berechtigung zum Erstellen von neoDraw-Bildern."), ["status" => 403]); }

    if ($upload_dir === false) { $upload_dir = wp_upload_dir()["path"]; }
    $filename = sanitize_file_name(pathinfo($filename ?: "neo-draw", PATHINFO_FILENAME));
    $filename = ($filename ?: "neo-draw") . ".svg";
    $filename = wp_unique_filename($upload_dir, $filename);
    if ($title === false) { $title = \NeoAnimate\NeoGlobal\preg_replace_better("/(.*)-?(\d+)?\.svg/", "$1", $filename); }
    if ($slug === false)  { $slug  = \NeoAnimate\NeoGlobal\preg_replace_better("/(.*)-?(\d+)?\.svg/", "$1", $filename); }

    $filepath = $upload_dir . "/" . $filename;
    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Create neoDraw file: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["postParentId" => $post_parent_id, "uploadDir" => $upload_dir, "filename" => $filename, "filepath" => $filepath, "title" => $title, "slug" => $slug, "dateTimestamp" => $date_timestamp, "svgSize" => strlen($svg_content)])));
    $success = \NeoAnimate\NeoGlobal\fs_file_put_contents($filepath, $svg_content);
    if (!$success) {
        return new \WP_Error("rest_forbidden", \NeoAnimate\NeoGlobal\neo__("Could not write to file.", "Konnte nicht in die Datei schreiben."), ["status" => 403]);
    }
    if ($date_timestamp !== null) { \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Touch neoDraw file: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["filepath" => $filepath, "oldFilemtime" => \NeoAnimate\NeoGlobal\fs_filemtime($filepath), "newFilemtime" => $date_timestamp]))); \NeoAnimate\NeoGlobal\fs_touch($filepath, $date_timestamp); }

    $attachment = [
        "post_mime_type" => "image/svg+xml",
        "post_title" => $title,
        "post_content" => "",
        "post_status" => "inherit",
        "post_parent" => $post_parent_id,
        "post_name" => $slug,
        "post_date"     => \NeoAnimate\NeoGlobal\wp_date_string(timestamp: $date_timestamp),
        "post_date_gmt" => \NeoAnimate\NeoGlobal\utc_date_string(timestamp: $date_timestamp),
    ];
    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Insert neoDraw attachment: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["filepath" => $filepath, "postParentId" => $post_parent_id, "postTitle" => $attachment["post_title"], "postName" => $attachment["post_name"], "postDate" => $attachment["post_date"], "postDateGmt" => $attachment["post_date_gmt"]])));
    $attach_id = wp_insert_attachment($attachment, $filepath);
    if ($attach_id === 0) { \NeoAnimate\NeoGlobal\throw_global_exception("Could not insert attachment."); }
    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Inserted neoDraw attachment: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["attachmentId" => $attach_id, "filepath" => $filepath, "imgUrl" => wp_get_attachment_url($attach_id)])));

    update_attachment_metadata($attach_id, $filepath);
    $new_img_url = wp_get_attachment_url($attach_id);
    return $new_img_url;
}

\NeoAnimate\NeoGlobal\register_rest_endpoint("/wp-json/neo/editor", "POST", fn () => \NeoAnimate\NeoGlobal\current_user_can__neo_draw(), function ($get_param) {
    interface_enable_svg_uploads_20250302();

    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Save image: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["img-url" => $get_param("img-url"), "force-new" => $get_param("force-new"), "filename" => $get_param("filename"), "force-title" => $get_param("force-title"), "force-slug" => $get_param("force-slug"), "inserted-from-img-url" => $get_param("inserted-from-img-url"), "post-id" => $get_param("post-id")])));

    $svg = $get_param("svg");
    if ($svg === null) { return new \WP_Error("rest_invalid_param", \NeoAnimate\NeoGlobal\neo__("Invalid data.", "Ungültige Daten."), ["status" => 400]); }
    $svg_metadata = \NeoAnimate\NeoGlobal\get_image_metadata($svg);
    if ($svg_metadata === false) { $svg_metadata = []; }
    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Save image metadata: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["isAnimated" => $svg_metadata["isAnimated"] ?? null, "isMotion" => $svg_metadata["isMotion"] ?? null, "width" => $svg_metadata["width"] ?? null, "height" => $svg_metadata["height"] ?? null, "svgSize" => strlen($svg)])));
    $img_url = $get_param("img-url") === null ? null : \NeoAnimate\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("img-url"));

    $filename       = $get_param("filename")    ?? false;
    $force_title    = $get_param("force-title") ?? false;
    $force_new      = $get_param("force-new")   ?? false;

    $force_upload_dir = false;
    $force_slug  = false;
    if ($get_param("inserted-from-img-url")) {
        $original_img_url = \NeoAnimate\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("inserted-from-img-url"));
        $original_img_id = attachment_url_to_postid($original_img_url);
        if ($original_img_id != 0) {
            $original_img_file = get_attached_file($original_img_id);
            if ($original_img_file && \NeoAnimate\NeoGlobal\fs_is_dir(dirname($original_img_file))) { $force_upload_dir = dirname($original_img_file); }

            if (!$filename) { $filename = basename($original_img_url); }

            $original_img_title = get_post_field("post_title", $original_img_id, context: "raw");
            if ($force_title === false) { $force_title = $original_img_title . " - neoDraw"; }

            $original_img_slug = get_post_field("post_name", $original_img_id, context: "raw");
            $force_slug = $original_img_slug . "-neodraw";
        }
        \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Import image into neoDraw: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["originalImgUrl" => $original_img_url, "originalImgId" => $original_img_id, "forceUploadDir" => $force_upload_dir, "filename" => $filename, "forceTitle" => $force_title, "forceSlug" => $force_slug, "forceNew" => true])));

        $force_new = true;
    }

    $new_image = ($img_url == "" || $force_new);
    if ($new_image) {
        $parent_post_id = $get_param("post-id") ?? -1;
        $img_url = add_svg_to_wp_media($svg, $parent_post_id, $force_upload_dir, $filename, $force_title, $force_slug);
        if (is_wp_error($img_url)) { return $img_url; }
    } else {
        if ($filename || $force_title || $force_slug) {
            return new \WP_Error("rest_invalid_param", \NeoAnimate\NeoGlobal\neo__("Invalid data. Cannot force when updating.", "Ungültige Daten. Kann beim Aktualisieren nicht erzwungen werden."), ["status" => 400]);
        }

        if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return new \WP_Error("rest_forbidden", \NeoAnimate\NeoGlobal\neo__("You do not have permissions to update neoDraw images.", "Sie haben keine Berechtigung zum Bearbeiten von neoDraw-Bildern."), ["status" => 403]); }
        $img_id = attachment_url_to_postid($img_url);
        if ($img_id === 0) { return new \WP_Error("rest_invalid_param", \NeoAnimate\NeoGlobal\neo__("Invalid image URL.", "Ungültige Bild-URL."), ["status" => 400]); }
        $file = get_attached_file($img_id);
        if (!$file) { return new \WP_Error("rest_invalid_param", \NeoAnimate\NeoGlobal\neo__("Invalid image file.", "Ungültige Bilddatei."), ["status" => 400]); }
        \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Update neoDraw file: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "imgUrl" => $img_url, "filepath" => $file, "oldFileSize" => \NeoAnimate\NeoGlobal\fs_file_exists($file) ? \NeoAnimate\NeoGlobal\fs_filesize($file) : null, "newSvgSize" => strlen($svg)])));
        $success = \NeoAnimate\NeoGlobal\fs_file_put_contents($file, $svg);
        if (!$success) { return new \WP_Error("rest_forbidden", \NeoAnimate\NeoGlobal\neo__("Could not write to file.", "Konnte nicht in die Datei schreiben."), ["status" => 403]); }
        update_attachment_metadata($img_id);
    }

    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "After neoDraw save maintenance: " . \NeoAnimate\NeoGlobal\json_encode_better(\NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgUrl" => $img_url, "imgId" => attachment_url_to_postid($img_url), "regenerateNeoOptimize" => true, "flushThirdPartyCaches" => true, "updateImageCachebustDate" => true])));
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoOptimize\interface_regenerate_image_20251030')($img_url);

    \NeoAnimate\NeoGlobal\flush_all_third_party_caches();

    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoImageCachebust\interface_update_last_image_cachebust_change_date_20260303')();
    return ["imgUrl" => \NeoAnimate\NeoGlobal\percent_encode_invalid_utf8_url_bytes($img_url), "imgId" => attachment_url_to_postid($img_url)];
});

\NeoAnimate\NeoGlobal\register_rest_endpoint("/wp-json/neo/draw-editor-img-id", "GET", fn () => \NeoAnimate\NeoGlobal\current_user_can__neo_draw(), function ($get_param) {
    $img_url = \NeoAnimate\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("img-url"));
    $img_url = \NeoAnimate\NeoGlobal\make_url_absolute($img_url);
    $img_id = attachment_url_to_postid($img_url);
    return ["imgId" => $img_id];
});

\NeoAnimate\NeoGlobal\register_rest_endpoint("/wp-json/neo/draw-db-entries-using-images", "GET", fn () => \NeoAnimate\NeoGlobal\current_user_can__global_db_entries_usage(), function ($get_param) {
    $img_url = \NeoAnimate\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("img-url"));
    $img_db_entries = \NeoAnimate\NeoGlobal\db_entries_image_usage_lookup([$img_url], max_sql_time_seconds: 3, include_content_preview: \NeoAnimate\NeoGlobal\current_user_can__global_db_entries_details());
    if ($img_db_entries === false) { return []; }
    return $img_db_entries[$img_url] ?? [];
});
