<?php
namespace NeoDuplicate\NeoDuplicate; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Image duplication engine for reliably duplicating an image with all metadata in the database using sanitized and escaped SQL commands, ensuring that all data is safely duplicated.
\NeoDuplicate\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-duplicate", \NeoDuplicate\NeoGlobal\add_or_update_query_params(admin_url("upload.php"), ["mode" => "list", "neo-library--suppress-redirect" => "true"]) . "#neo-duplicate--open-tutorial");
});

\NeoDuplicate\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {
        ?><neo-setting-neo-duplicate>
            <div slot="left">
                <h3><?php \NeoDuplicate\NeoGlobal\echo_neo__("Duplicate media files", "Medien duplizieren") ?></h3>
                <p><?php \NeoDuplicate\NeoGlobal\echo_neo__("Duplicated images get today's creation date.", "Beim Duplizieren wird das heutige Datum als Erstelldatum gesetzt.") ?></p>
            </div>
            <div slot="right">
                <?php \NeoDuplicate\NeoGlobal\echo_switch_for_option("neo_duplicate__use_today_as_upload_date", \NeoDuplicate\NeoGlobal\neo__("Use today's date", "Heutiges Datum nutzen")) ?> 
            </div>
        </neo-setting-neo-duplicate><?php
    };
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoSettings\interface_add_neo_setting_20260326')("neo-duplicate", $settings_render_callback);
});

\NeoDuplicate\NeoGlobal\add_filter_hook("media_row_actions", function ($actions, $img_post, $detached) {
    if (!\NeoDuplicate\NeoGlobal\current_user_can__neo_duplicate()) { return $actions; }
    $img_url = wp_get_attachment_url($img_post->ID);
    if (!$img_url) { return $actions; }
    $duplicate_button_html = '<button class="button-link neo-duplicate--media-library-list-inline-duplicate-button" style="color: gray; pointer-events: none; cursor: default;" data-neo-duplicate--img-url="' . esc_attr($img_url) . '">' . \NeoDuplicate\NeoGlobal\neo__("neoDuplicate", "neoDuplizieren") . '</button>';
    $actions_with_duplicate = [];
    foreach ($actions as $action_key => $action_html) { $actions_with_duplicate[$action_key] = $action_html; if ($action_key === "neo_rename") { $actions_with_duplicate["neo_duplicate"] = $duplicate_button_html; } }
    if (!isset($actions_with_duplicate["neo_duplicate"])) { $actions_with_duplicate["neo_duplicate"] = $duplicate_button_html; }
    return $actions_with_duplicate;
});
\NeoDuplicate\NeoGlobal\add_action_hook("current_screen", function () {
    global $pagenow;
    if ($pagenow !== "upload.php") { return; }
    if (!\NeoDuplicate\NeoGlobal\current_user_can__neo_duplicate()) { return; }
    [$neo_library_page_slug, $interface_ok] = \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoLibrary\interface_neo_library_menu_page_slug_20250618')(); if ($interface_ok && \NeoDuplicate\NeoGlobal\query_param("page") === $neo_library_page_slug) { return; }
    \NeoDuplicate\NeoGlobal\enqueue_js("neo-duplicate--classic-wp-media-library-list.js");
    \NeoDuplicate\NeoGlobal\enqueue_js("neo-duplicate--classic-wp-media-library-grid.js");
});

\NeoDuplicate\NeoGlobal\register_rest_endpoint("/wp-json/neo/duplicate", "POST", fn () => \NeoDuplicate\NeoGlobal\current_user_can__neo_duplicate(), function ($get_param) {
    return \NeoDuplicate\NeoGlobal\synclock_dir(\NeoDuplicate\NeoGlobal\uploads_dir(), timeout: 60, scope: "neo-duplicate", callback: function () use ($get_param) {
        global $wpdb;
        $img_url = \NeoDuplicate\NeoGlobal\percent_decode_invalid_utf8_url_bytes(\NeoDuplicate\NeoGlobal\remove_all_query_params((string) $get_param("img-url"))); $img_id = attachment_url_to_postid($img_url); \NeoDuplicate\NeoGlobal\global_log_with_module_name("neo-duplicate", "Duplicate media request: " . \NeoDuplicate\NeoGlobal\json_encode_better(\NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgUrl" => $img_url, "imgId" => $img_id])));
        if ($img_id === 0) { \NeoDuplicate\NeoGlobal\throw_global_exception(\NeoDuplicate\NeoGlobal\neo__("Media file not found.", "Mediendatei nicht gefunden."), status_code: 404); }
        $old_post = get_post($img_id, ARRAY_A);
        if (!$old_post) { \NeoDuplicate\NeoGlobal\throw_global_exception("Attachment post not found.", status_code: 404); }
        $old_attached_path_absolute = get_attached_file($img_id);
        if ($old_attached_path_absolute === false || !str_starts_with($old_attached_path_absolute, \NeoDuplicate\NeoGlobal\uploads_dir() . "/")) { \NeoDuplicate\NeoGlobal\throw_global_exception(\NeoDuplicate\NeoGlobal\neo__("Media file is not in the uploads folder.", "Mediendatei liegt nicht im Uploads-Ordner."), status_code: 403); }
        if (!\NeoDuplicate\NeoGlobal\fs_is_file($old_attached_path_absolute)) {
            return new \WP_Error("neo_duplicate__local_media_file_missing", \NeoDuplicate\NeoGlobal\neo__("Local media file is missing.", "Lokale Mediendatei fehlt."), ["status" => 404, "pathRel" => \NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_url_bytes(str_replace(\NeoDuplicate\NeoGlobal\uploads_dir() . "/", "", $old_attached_path_absolute))]);
        }
        $normalize_upload_path_rel = function ($path_rel) {
            $path_rel = str_replace("\\", "/", (string) $path_rel);
            if ($path_rel === "" || str_starts_with($path_rel, "/") || str_contains($path_rel, "\0")) { \NeoDuplicate\NeoGlobal\throw_global_exception("Invalid media metadata path.", status_code: 400); }
            $path_parts = explode("/", $path_rel);
            foreach ($path_parts as $path_part) { if ($path_part === "" || $path_part === "." || $path_part === "..") { \NeoDuplicate\NeoGlobal\throw_global_exception("Invalid media metadata path.", status_code: 400); } }
            return implode("/", $path_parts);
        };
        $old_attached_path_rel = $normalize_upload_path_rel(str_replace(\NeoDuplicate\NeoGlobal\uploads_dir() . "/", "", $old_attached_path_absolute)); $old_metadata = \NeoDuplicate\NeoGlobal\post_meta($img_id, "_wp_attachment_metadata"); if (!is_array($old_metadata)) { $old_metadata = []; }
        $old_backup_sizes = \NeoDuplicate\NeoGlobal\post_meta($img_id, "_wp_attachment_backup_sizes"); if (!is_array($old_backup_sizes)) { $old_backup_sizes = []; }
        $path_dir_rel = function ($path_rel) { $dir_rel = dirname($path_rel); return $dir_rel === "." ? "" : $dir_rel; };
        $file_path_from_meta = function ($file, $dir_rel) use ($normalize_upload_path_rel) { return $normalize_upload_path_rel(str_contains((string) $file, "/") ? (string) $file : \NeoDuplicate\NeoGlobal\path_join_rel($dir_rel, (string) $file)); };
        $additional_file_metadata_keys = ["source_image", "animated_video", "animated_video_poster"];
        $collect_paths = function ($attached_path_rel, $metadata, $backup_sizes) use ($path_dir_rel, $file_path_from_meta, $additional_file_metadata_keys) {
            $metadata_file_rel = !empty($metadata["file"]) ? $file_path_from_meta($metadata["file"], $path_dir_rel($attached_path_rel)) : $attached_path_rel; $paths = [$attached_path_rel]; $metadata_dir_rel = $path_dir_rel($metadata_file_rel);
            if (!empty($metadata["file"])) { $paths[] = $metadata_file_rel; }
            if (!empty($metadata["original_image"])) { $paths[] = $file_path_from_meta($metadata["original_image"], $metadata_dir_rel); }
            foreach ($additional_file_metadata_keys as $metadata_key) { if (!empty($metadata[$metadata_key])) { $paths[] = $file_path_from_meta($metadata[$metadata_key], $metadata_dir_rel); } }
            foreach (($metadata["sizes"] ?? []) as $size) { if (!empty($size["file"])) { $paths[] = $file_path_from_meta($size["file"], $metadata_dir_rel); } }
            foreach ($backup_sizes as $backup_size) { if (!empty($backup_size["file"])) { $paths[] = $file_path_from_meta($backup_size["file"], $metadata_dir_rel); } }
            return \NeoDuplicate\NeoGlobal\array_unique_better($paths);
        };
        $update_meta_file = function ($file, $dir_rel, $path_mapping) use ($file_path_from_meta) { $old_path_rel = $file_path_from_meta($file, $dir_rel); if (!isset($path_mapping[$old_path_rel])) { return $file; } return str_contains((string) $file, "/") ? $path_mapping[$old_path_rel] : basename($path_mapping[$old_path_rel]); };
        $old_paths = $collect_paths($old_attached_path_rel, $old_metadata, $old_backup_sizes); \NeoDuplicate\NeoGlobal\global_log_with_module_name("neo-duplicate", "Duplicate media paths collected: " . \NeoDuplicate\NeoGlobal\json_encode_better(\NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldAttachedPathRel" => $old_attached_path_rel, "pathCount" => count($old_paths), "oldPaths" => $old_paths])));
        $old_metadata_file_rel = !empty($old_metadata["file"]) ? $file_path_from_meta($old_metadata["file"], $path_dir_rel($old_attached_path_rel)) : $old_attached_path_rel; $old_metadata_dir_rel = $path_dir_rel($old_metadata_file_rel);
        $old_original_path_rel = !empty($old_metadata["original_image"]) ? $file_path_from_meta($old_metadata["original_image"], $old_metadata_dir_rel) : null; $old_has_original_image_file = $old_original_path_rel !== null && \NeoDuplicate\NeoGlobal\fs_is_file(\NeoDuplicate\NeoGlobal\uploads_dir() . "/" . $old_original_path_rel); $old_canonical_filename = $old_has_original_image_file ? basename($old_original_path_rel) : basename($old_attached_path_rel);
        \NeoDuplicate\NeoGlobal\global_log_with_module_name("neo-duplicate", "Duplicate media source analyzed: " . \NeoDuplicate\NeoGlobal\json_encode_better(\NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldMetadataFileRel" => $old_metadata_file_rel, "oldOriginalPathRel" => $old_original_path_rel, "oldHasOriginalImageFile" => $old_has_original_image_file, "oldCanonicalFilename" => $old_canonical_filename])));
        $target_dir_rel = $path_dir_rel($old_has_original_image_file ? $old_original_path_rel : $old_attached_path_rel); $target_dir_absolute = untrailingslashit($target_dir_rel === "" ? \NeoDuplicate\NeoGlobal\uploads_dir() : \NeoDuplicate\NeoGlobal\uploads_dir() . "/" . $target_dir_rel); \NeoDuplicate\NeoGlobal\mkdir_better($target_dir_absolute);
        $build_path_mapping = function ($new_canonical_filename) use ($old_paths, $old_canonical_filename, $path_dir_rel) {
            $old_canonical_stem = pathinfo($old_canonical_filename, PATHINFO_FILENAME); $new_canonical_stem = pathinfo($new_canonical_filename, PATHINFO_FILENAME); $path_mapping = [];
            foreach ($old_paths as $old_path_rel) { $old_filename = basename($old_path_rel); $old_stem = pathinfo($old_filename, PATHINFO_FILENAME); $old_extension = pathinfo($old_filename, PATHINFO_EXTENSION); $new_stem = str_starts_with($old_stem, $old_canonical_stem) ? $new_canonical_stem . substr($old_stem, strlen($old_canonical_stem)) : $new_canonical_stem . "-" . $old_stem; $path_mapping[$old_path_rel] = \NeoDuplicate\NeoGlobal\path_join_rel($path_dir_rel($old_path_rel), $new_stem . ($old_extension === "" ? "" : "." . $old_extension)); }
            return $path_mapping;
        };
        $unique_filename_base_stem = pathinfo($old_canonical_filename, PATHINFO_FILENAME); $unique_filename_base_extension = pathinfo($old_canonical_filename, PATHINFO_EXTENSION);
        while (\NeoDuplicate\NeoGlobal\preg_match_better("/^(.*)-[0-9]+$/", $unique_filename_base_stem, $numbered_filename_match) && \NeoDuplicate\NeoGlobal\fs_is_file($target_dir_absolute . "/" . $numbered_filename_match[1] . ($unique_filename_base_extension === "" ? "" : "." . $unique_filename_base_extension))) { $unique_filename_base_stem = $numbered_filename_match[1]; }
        $unique_filename_base = $unique_filename_base_stem . ($unique_filename_base_extension === "" ? "" : "." . $unique_filename_base_extension);
        $new_canonical_filename = null; $path_mapping = null;
        for ($i = 0; $i < 100; $i++) { $candidate_filename = wp_unique_filename($target_dir_absolute, $i === 0 ? $unique_filename_base : $unique_filename_base_stem . "-" . ($i + 1) . ($unique_filename_base_extension === "" ? "" : "." . $unique_filename_base_extension)); $candidate_mapping = $build_path_mapping($candidate_filename); $has_collision = false; foreach ($candidate_mapping as $old_path_rel => $new_path_rel) { if ($old_path_rel !== $new_path_rel && \NeoDuplicate\NeoGlobal\fs_file_exists(\NeoDuplicate\NeoGlobal\uploads_dir() . "/" . $new_path_rel)) { $has_collision = true; break; } } if (!$has_collision) { $new_canonical_filename = $candidate_filename; $path_mapping = $candidate_mapping; break; } }
        if ($new_canonical_filename === null || $path_mapping === null) { \NeoDuplicate\NeoGlobal\throw_global_exception("Could not find a unique filename for duplicate.", status_code: 500); }
        \NeoDuplicate\NeoGlobal\global_log_with_module_name("neo-duplicate", "Duplicate media target mapped: " . \NeoDuplicate\NeoGlobal\json_encode_better(\NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "newCanonicalFilename" => $new_canonical_filename, "mappingCount" => count($path_mapping), "pathMapping" => $path_mapping])));
        $new_attached_path_rel = $path_mapping[$old_attached_path_rel]; $new_attached_path_absolute = \NeoDuplicate\NeoGlobal\uploads_dir() . "/" . $new_attached_path_rel; $new_slug = wp_unique_post_slug(sanitize_title(pathinfo($new_attached_path_rel, PATHINFO_FILENAME)) ?: "attachment", 0, $old_post["post_status"], "attachment", (int) $old_post["post_parent"]);
        $use_today_as_upload_date = \NeoDuplicate\NeoGlobal\option__neo_duplicate__use_today_as_upload_date(); $post_date = $use_today_as_upload_date ? \NeoDuplicate\NeoGlobal\wp_date_string() : $old_post["post_date"]; $post_date_gmt = $use_today_as_upload_date ? \NeoDuplicate\NeoGlobal\utc_date_string() : $old_post["post_date_gmt"]; $post_modified = $use_today_as_upload_date ? $post_date : $old_post["post_modified"]; $post_modified_gmt = $use_today_as_upload_date ? $post_date_gmt : $old_post["post_modified_gmt"];
        $created_files = []; $new_img_id = null;
        try {
            foreach ($path_mapping as $old_path_rel => $new_path_rel) { $old_path_absolute = \NeoDuplicate\NeoGlobal\uploads_dir() . "/" . $old_path_rel; $new_path_absolute = \NeoDuplicate\NeoGlobal\uploads_dir() . "/" . $new_path_rel; if (!\NeoDuplicate\NeoGlobal\fs_is_file($old_path_absolute)) { \NeoDuplicate\NeoGlobal\global_warn_with_module_name("neo-duplicate", "neoDuplicate source file missing during copy: " . \NeoDuplicate\NeoGlobal\json_encode_better(\NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldPathRel" => $old_path_rel, "newPathRel" => $new_path_rel]))); continue; } \NeoDuplicate\NeoGlobal\mkdir_better(dirname($new_path_absolute)); if (!\NeoDuplicate\NeoGlobal\fs_copy($old_path_absolute, $new_path_absolute)) { \NeoDuplicate\NeoGlobal\throw_global_exception(\NeoDuplicate\NeoGlobal\neo__("Could not copy media file.", "Mediendatei konnte nicht kopiert werden.") . " " . $old_path_absolute, status_code: 500); } $created_files[] = $new_path_absolute; if (!$use_today_as_upload_date) { \NeoDuplicate\NeoGlobal\fs_touch($new_path_absolute, \NeoDuplicate\NeoGlobal\fs_filemtime($old_path_absolute)); } }
            $new_post = $old_post; unset($new_post["ID"], $new_post["guid"], $new_post["filter"]); $new_post["post_name"] = $new_slug; $new_post["post_date"] = $post_date; $new_post["post_date_gmt"] = $post_date_gmt; $new_post["post_modified"] = $post_modified; $new_post["post_modified_gmt"] = $post_modified_gmt; $new_post["post_mime_type"] = get_post_mime_type($img_id); $new_post["guid"] = \NeoDuplicate\NeoGlobal\uploads_url() . "/" . $new_attached_path_rel;
            $new_img_id = wp_insert_attachment($new_post, $new_attached_path_absolute, (int) $old_post["post_parent"]);
            if (is_wp_error($new_img_id) || !$new_img_id) { \NeoDuplicate\NeoGlobal\throw_global_exception("Could not insert duplicate attachment.", status_code: 500); }
            delete_post_meta($new_img_id, "_wp_attached_file"); delete_post_meta($new_img_id, "_wp_attachment_metadata");
            foreach ($wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d ORDER BY meta_id ASC", $img_id), ARRAY_A) as $meta_row) { if (in_array($meta_row["meta_key"], ["_wp_attached_file", "_wp_attachment_metadata", "_wp_attachment_backup_sizes"], true)) { continue; } if ($wpdb->insert($wpdb->postmeta, ["post_id" => $new_img_id, "meta_key" => $meta_row["meta_key"], "meta_value" => $meta_row["meta_value"]]) === false) { \NeoDuplicate\NeoGlobal\throw_global_exception("Could not copy attachment metadata: " . $wpdb->last_error, status_code: 500); } } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. Also, only the meta data for one post are fetched, so this is no performance problem. */
            wp_cache_delete($new_img_id, "post_meta");
            $new_metadata = unserialize(serialize($old_metadata)); if (isset($new_metadata["file"])) { $new_metadata["file"] = $path_mapping[$old_metadata_file_rel] ?? $new_attached_path_rel; } if (isset($new_metadata["original_image"]) && $old_has_original_image_file) { $new_metadata["original_image"] = basename($path_mapping[$old_original_path_rel] ?? $new_metadata["original_image"]); } else { unset($new_metadata["original_image"]); } foreach ($additional_file_metadata_keys as $metadata_key) { if (!empty($new_metadata[$metadata_key])) { $new_metadata[$metadata_key] = $update_meta_file($new_metadata[$metadata_key], $old_metadata_dir_rel, $path_mapping); } } foreach (($new_metadata["sizes"] ?? []) as $size_name => $size) { if (!empty($size["file"])) { $new_metadata["sizes"][$size_name]["file"] = $update_meta_file($size["file"], $old_metadata_dir_rel, $path_mapping); } }
            $new_backup_sizes = unserialize(serialize($old_backup_sizes)); foreach (($new_backup_sizes ?? []) as $backup_size_name => $backup_size) { if (!empty($backup_size["file"])) { $new_backup_sizes[$backup_size_name]["file"] = $update_meta_file($backup_size["file"], $old_metadata_dir_rel, $path_mapping); } }
            update_attached_file($new_img_id, $new_attached_path_absolute); if (get_post_meta($new_img_id, "_wp_attached_file", true) !== $new_attached_path_rel) { \NeoDuplicate\NeoGlobal\throw_global_exception("Could not save attached file metadata.", status_code: 500); }
            wp_update_attachment_metadata($new_img_id, $new_metadata); $saved_metadata = \NeoDuplicate\NeoGlobal\post_meta($new_img_id, "_wp_attachment_metadata"); if ($saved_metadata != $new_metadata && !($new_metadata === [] && $saved_metadata === null)) { \NeoDuplicate\NeoGlobal\throw_global_exception("Could not save attachment metadata.", status_code: 500); }
            if ($new_backup_sizes !== []) { update_post_meta($new_img_id, "_wp_attachment_backup_sizes", wp_slash($new_backup_sizes)); }
        } catch (\Throwable $e) {
            \NeoDuplicate\NeoGlobal\global_warn_with_module_name("neo-duplicate", "neoDuplicate failed and starts rollback: " . \NeoDuplicate\NeoGlobal\json_encode_better(\NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "newImgId" => $new_img_id, "createdFiles" => $created_files, "error" => $e->getMessage()])));
            if ($new_img_id) { wp_delete_attachment((int) $new_img_id, true); }
            foreach (array_reverse(\NeoDuplicate\NeoGlobal\array_unique_better($created_files)) as $created_file) { if (\NeoDuplicate\NeoGlobal\fs_is_file($created_file) && !\NeoDuplicate\NeoGlobal\fs_unlink($created_file)) { \NeoDuplicate\NeoGlobal\global_warn_with_module_name("neo-duplicate", "neoDuplicate rollback could not delete created file: " . $created_file); } }
            throw $e;
        }
        \NeoDuplicate\NeoGlobal\flush_all_third_party_caches(); wp_cache_flush(); \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoImageCachebust\interface_update_last_image_cachebust_change_date_20260303')(); clean_post_cache($new_img_id);
        \NeoDuplicate\NeoGlobal\global_log_with_module_name("neo-duplicate", "Duplicate media file: " . \NeoDuplicate\NeoGlobal\json_encode_better(\NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "newImgId" => $new_img_id, "oldImgUrl" => wp_get_attachment_url($img_id), "newImgUrl" => wp_get_attachment_url($new_img_id), "useTodayAsUploadDate" => $use_today_as_upload_date])));
        return ["imgUrl" => \NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_url_bytes(wp_get_attachment_url($new_img_id)), "postId" => $new_img_id, "title" => get_post_field("post_title", $new_img_id, context: "raw"), "slug" => get_post_field("post_name", $new_img_id, context: "raw"), "pathRel" => \NeoDuplicate\NeoGlobal\percent_encode_invalid_utf8_url_bytes($new_attached_path_rel), "uploadDate" => get_post($new_img_id)->post_date, "useTodayAsUploadDate" => $use_today_as_upload_date];
    });
});
