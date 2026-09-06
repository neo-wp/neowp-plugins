<?php
namespace NeoReplace\NeoReplace; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoReplace\NeoGlobal\register_rest_endpoint("/wp-json/neo/replace", "POST", fn () => \NeoReplace\NeoGlobal\current_user_can__neo_replace(), function ($get_param) {
    return \NeoReplace\NeoGlobal\synclock_dir(\NeoReplace\NeoGlobal\uploads_dir(), timeout: 60, scope: "neo-replace", callback: function () use ($get_param) {
        global $wpdb;

        $replace_validate_metadata_path = function ($path_rel, $log_data) {
            if (!is_string($path_rel)) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace skipped invalid media path type: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(array_merge($log_data, ["file" => $path_rel])))); return null; }
            if ($path_rel === "") { return null; }
            if (str_starts_with($path_rel, "/") || str_contains($path_rel, "\0")) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace skipped invalid media path: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(array_merge($log_data, ["file" => $path_rel])))); return null; }
            foreach (explode("/", $path_rel) as $path_part) { if ($path_part === "" || $path_part === "." || $path_part === "..") { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace skipped invalid media path segment: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(array_merge($log_data, ["file" => $path_rel])))); return null; } }
            return $path_rel;
        };

        $replace_collect_size_paths = function ($main_path_rel, $metadata, $img_id = null) use ($replace_validate_metadata_path) {
            $metadata_file_path_rel = $replace_validate_metadata_path($metadata["file"] ?? $main_path_rel, ["imgId" => $img_id, "source" => "metadata-file"]); if ($metadata_file_path_rel === null) { $metadata_file_path_rel = $main_path_rel; }
            $dir_rel = dirname($metadata_file_path_rel); if ($dir_rel === ".") { $dir_rel = ""; }
            $size_paths = [];
            foreach (($metadata["sizes"] ?? []) as $size_name => $size) {
                if (!is_array($size)) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace skipped invalid media size metadata type: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "sizeName" => $size_name, "size" => $size]))); continue; }
                if (empty($size["file"])) { continue; }
                $size_file_path_rel = $replace_validate_metadata_path($size["file"], ["imgId" => $img_id, "sizeName" => $size_name, "source" => "metadata-size"]); if ($size_file_path_rel === null) { continue; }
                $size_paths[$size_name] = ["path_rel" => str_contains($size_file_path_rel, "/") ? $size_file_path_rel : \NeoReplace\NeoGlobal\path_join_rel($dir_rel, $size_file_path_rel), "width" => intval($size["width"] ?? 0), "height" => intval($size["height"] ?? 0)];
            }
            return $size_paths;
        };

        \NeoReplace\NeoGlobal\add_filter_hook("big_image_size_threshold", function () { return false; });
        \NeoReplace\NeoGlobal\add_filter_hook("wp_image_maybe_exif_rotate", function () { return false; });
        if ($get_param("debug-replace-check-force-sizes") === "true" && \NeoReplace\NeoGlobal\current_user_can__neo_debug()) { \NeoReplace\NeoGlobal\add_filter_hook("intermediate_image_sizes_advanced:max", function ($sizes) { return array_merge($sizes, ["thumbnail" => ["width" => 150, "height" => 150, "crop" => true], "medium" => ["width" => 300, "height" => 300, "crop" => false]]); }); }
        require_once(ABSPATH . "wp-admin/includes/file.php"); require_once(ABSPATH . "wp-admin/includes/media.php"); require_once(ABSPATH . "wp-admin/includes/image.php"); /* This authenticated REST endpoint requires WordPress's upload and attachment APIs, which are not loaded for REST requests. #suppressLinterWporgDirectoryConstantCheck #suppressLinterWPorgAutoCoreIncludeCheck */

        $old_img_url = \NeoReplace\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("img-url")); $img_id = attachment_url_to_postid(\NeoReplace\NeoGlobal\remove_all_query_params($old_img_url));
        if ($img_id === 0) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Media file not found.", "Mediendatei nicht gefunden."), status_code: 404); }
        $old_img_url = wp_get_attachment_url($img_id);
        $uploaded_file = $get_param("file"); $source_uploaded_file = $get_param("source-file");
        if (!is_array($uploaded_file) || ($uploaded_file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Upload failed.", "Upload fehlgeschlagen."), status_code: 400); }
        if ($source_uploaded_file !== null && (!is_array($source_uploaded_file) || ($source_uploaded_file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Source upload failed.", "Upload der Quelldatei fehlgeschlagen."), status_code: 400); }
        \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace button clicked: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "imgUrl" => $old_img_url, "uploadedName" => $uploaded_file["name"] ?? "", "uploadedSize" => $uploaded_file["size"] ?? null, "uploadedType" => $uploaded_file["type"] ?? "", "sourceUploadedName" => $source_uploaded_file["name"] ?? null, "sourceUploadedSize" => $source_uploaded_file["size"] ?? null, "useUploadedFilename" => $get_param("use-uploaded-filename") === "true", "updateUploadDate" => $get_param("update-upload-date") === "true"])));
        \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoDraw\interface_enable_svg_uploads_20250302')();
        $type_check = wp_check_filetype_and_ext($uploaded_file["tmp_name"], $uploaded_file["name"]);
        if (empty($type_check["ext"]) || empty($type_check["type"])) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("This file type is not allowed.", "Dieser Dateityp ist nicht erlaubt."), status_code: 400); }
        $source_type_check = null; if ($source_uploaded_file !== null) { $source_type_check = wp_check_filetype_and_ext($source_uploaded_file["tmp_name"], $source_uploaded_file["name"]); }
        if ($source_uploaded_file !== null && !($type_check["type"] === "image/jpeg" && in_array($source_type_check["type"] ?? "", ["image/heic", "image/heif"], true) && in_array(strtolower($source_type_check["ext"] ?? ""), ["heic", "heif"], true))) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Invalid HEIC source file.", "Ungültige HEIC-Quelldatei."), status_code: 400); }
        $old_attached_path_absolute = get_attached_file($img_id);
        if ($old_attached_path_absolute === false || !str_starts_with($old_attached_path_absolute, \NeoReplace\NeoGlobal\uploads_dir() . "/")) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Media file is not in the uploads folder.", "Mediendatei liegt nicht im Uploads-Ordner."), status_code: 403); }
        if (!\NeoReplace\NeoGlobal\fs_file_exists($old_attached_path_absolute)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Local media file is missing.", "Lokale Mediendatei fehlt."), status_code: 404); }

        $old_attached_path_rel = \NeoReplace\NeoGlobal\str_replace_start(\NeoReplace\NeoGlobal\uploads_dir() . "/", "", $old_attached_path_absolute); if ($replace_validate_metadata_path($old_attached_path_rel, ["imgId" => $img_id, "source" => "attached-file"]) === null) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Media file is not in the uploads folder.", "Mediendatei liegt nicht im Uploads-Ordner."), status_code: 403); } $old_metadata = \NeoReplace\NeoGlobal\post_meta($img_id, "_wp_attachment_metadata"); if (!is_array($old_metadata)) { $old_metadata = []; }
        $old_backup_sizes = \NeoReplace\NeoGlobal\post_meta($img_id, "_wp_attachment_backup_sizes"); if (!is_array($old_backup_sizes)) { $old_backup_sizes = []; }
        $old_attached_dir_rel = dirname($old_attached_path_rel); if ($old_attached_dir_rel === ".") { $old_attached_dir_rel = ""; }
        $old_original_image_path_rel = null; if (!empty($old_metadata["original_image"])) { $old_original_image_file = $replace_validate_metadata_path($old_metadata["original_image"], ["imgId" => $img_id, "source" => "metadata-original-image"]); if ($old_original_image_file !== null) { $old_original_image_path_rel = \NeoReplace\NeoGlobal\path_join_rel($old_attached_dir_rel, $old_original_image_file); } }
        $old_main_path_rel = $old_original_image_path_rel ?? $old_attached_path_rel;
        $use_uploaded_filename = $get_param("use-uploaded-filename") === "true"; $update_upload_date = $get_param("update-upload-date") === "true"; $old_extension = strtolower(pathinfo($old_main_path_rel, PATHINFO_EXTENSION)); $new_extension = strtolower($type_check["ext"]);
        $target_dir_rel = dirname($old_main_path_rel); if ($target_dir_rel === ".") { $target_dir_rel = ""; } $target_dir_absolute = untrailingslashit($target_dir_rel === "" ? \NeoReplace\NeoGlobal\uploads_dir() : \NeoReplace\NeoGlobal\uploads_dir() . "/" . $target_dir_rel); \NeoReplace\NeoGlobal\mkdir_better($target_dir_absolute);
        $candidate_filename = $use_uploaded_filename ? sanitize_file_name(pathinfo($uploaded_file["name"], PATHINFO_FILENAME) . "." . $new_extension) : pathinfo($old_main_path_rel, PATHINFO_FILENAME) . "." . $new_extension;
        if (pathinfo($candidate_filename, PATHINFO_FILENAME) === "") { $candidate_filename = "file." . $new_extension; }

        $old_paths_dir_rel = dirname($old_attached_path_rel); if ($old_paths_dir_rel === ".") { $old_paths_dir_rel = ""; } $old_paths = [$old_attached_path_rel]; $old_companion_paths = [];
        if ($old_original_image_path_rel !== null) { $old_paths[] = $old_original_image_path_rel; }
        foreach (["source_image", "animated_video", "animated_video_poster"] as $companion_key) {
            if (empty($old_metadata[$companion_key])) { continue; }
            $companion_file_path_rel = $replace_validate_metadata_path($old_metadata[$companion_key], ["imgId" => $img_id, "source" => "metadata-" . $companion_key]); if ($companion_file_path_rel === null) { continue; }
            $old_companion_paths[$companion_key] = str_contains($companion_file_path_rel, "/") ? $companion_file_path_rel : \NeoReplace\NeoGlobal\path_join_rel($old_paths_dir_rel, $companion_file_path_rel); $old_paths[] = $old_companion_paths[$companion_key];
        }
        foreach ($replace_collect_size_paths($old_attached_path_rel, $old_metadata, $img_id) as $size) { $old_paths[] = $size["path_rel"]; }
        foreach (($old_backup_sizes ?: []) as $backup_size_name => $backup_size) {
            if (!is_array($backup_size)) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace skipped invalid backup size metadata type: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "backupSize" => $backup_size_name, "backupSizeData" => $backup_size]))); continue; }
            if (empty($backup_size["file"])) { continue; }
            $backup_file_path_rel = $replace_validate_metadata_path($backup_size["file"], ["imgId" => $img_id, "backupSize" => $backup_size_name, "source" => "backup-size"]); if ($backup_file_path_rel === null) { continue; }
            $old_paths[] = str_contains($backup_file_path_rel, "/") ? $backup_file_path_rel : \NeoReplace\NeoGlobal\path_join_rel($old_paths_dir_rel, $backup_file_path_rel);
        }
        foreach (\NeoReplace\NeoGlobal\array_unique_better($old_paths) as $path_rel) {
            foreach (["webp", "avif"] as $alternative_extension) {
                foreach ([\NeoReplace\NeoGlobal\preg_replace_better("/\.[^.]+$/", "." . $alternative_extension, $path_rel), $path_rel . "." . $alternative_extension] as $alternative_path_rel) {
                    if (\NeoReplace\NeoGlobal\fs_file_exists(\NeoReplace\NeoGlobal\uploads_dir() . "/" . $alternative_path_rel) && attachment_url_to_postid(\NeoReplace\NeoGlobal\uploads_url() . "/" . $alternative_path_rel) === 0) { $old_paths[] = $alternative_path_rel; }
                }
            }
        }
        $old_paths = \NeoReplace\NeoGlobal\array_unique_better($old_paths); \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace old paths: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "count" => count($old_paths), "paths" => $old_paths, "backupSizeCount" => count($old_backup_sizes), "backupSizes" => $old_backup_sizes]))); $candidate_path_rel = \NeoReplace\NeoGlobal\path_join_rel($target_dir_rel, $candidate_filename);
        if (!in_array($candidate_path_rel, $old_paths, true) && \NeoReplace\NeoGlobal\fs_file_exists(\NeoReplace\NeoGlobal\uploads_dir() . "/" . $candidate_path_rel)) { $candidate_filename = wp_unique_filename($target_dir_absolute, $candidate_filename); }
        $target_path_rel = \NeoReplace\NeoGlobal\path_join_rel($target_dir_rel, $candidate_filename); $target_path_absolute = \NeoReplace\NeoGlobal\uploads_dir() . "/" . $target_path_rel; $target_temp_path_absolute = $target_path_absolute . ".neo-replace-" . \NeoReplace\NeoGlobal\unique_planck_date() . ".tmp"; $handled_upload_path = null;
        $source_target_path_rel = null; $source_target_path_absolute = null; $source_temp_path_absolute = null; $handled_source_upload_path = null;
        if ($source_uploaded_file !== null) { $source_candidate_filename = pathinfo($candidate_filename, PATHINFO_FILENAME) . "." . strtolower($source_type_check["ext"]); $source_candidate_path_rel = \NeoReplace\NeoGlobal\path_join_rel($target_dir_rel, $source_candidate_filename); if (!in_array($source_candidate_path_rel, $old_paths, true) && \NeoReplace\NeoGlobal\fs_file_exists(\NeoReplace\NeoGlobal\uploads_dir() . "/" . $source_candidate_path_rel)) { $source_candidate_filename = wp_unique_filename($target_dir_absolute, $source_candidate_filename); } $source_target_path_rel = \NeoReplace\NeoGlobal\path_join_rel($target_dir_rel, $source_candidate_filename); $source_target_path_absolute = \NeoReplace\NeoGlobal\uploads_dir() . "/" . $source_target_path_rel; $source_temp_path_absolute = $source_target_path_absolute . ".neo-replace-" . \NeoReplace\NeoGlobal\unique_planck_date() . ".tmp"; }
        \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace file: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldPathRel" => $old_attached_path_rel, "oldMainPathRel" => $old_main_path_rel, "newPathRel" => $target_path_rel, "oldMimeType" => get_post_mime_type($img_id), "newMimeType" => $type_check["type"], "changes" => true])));

        $backup_dir = \NeoReplace\NeoGlobal\cache_path("neo-replace") . "/" . \NeoReplace\NeoGlobal\unique_planck_date(); \NeoReplace\NeoGlobal\mkdir_better($backup_dir); $backup_files = [];

        try {
            foreach ($old_paths as $old_path_rel) {
                $original_path_absolute = \NeoReplace\NeoGlobal\uploads_dir() . "/" . $old_path_rel;
                if (!\NeoReplace\NeoGlobal\fs_is_file($original_path_absolute)) { continue; }
                $backup_path_absolute = $backup_dir . "/" . hash("sha256", $old_path_rel) . "-" . basename($old_path_rel);
                if (!\NeoReplace\NeoGlobal\fs_copy($original_path_absolute, $backup_path_absolute)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Could not create rollback backup.", "Rollback-Sicherung konnte nicht erstellt werden.") . " " . $original_path_absolute, status_code: 500); }
                $backup_files[] = ["original" => $original_path_absolute, "backup" => $backup_path_absolute];
            }
        } catch (\Throwable $e) {
            \NeoReplace\NeoGlobal\delete_all($backup_dir);
            throw $e;
        }
        $created_files = []; $transaction_started = false; $replace_success = false; $old_files_touched = false;
        $remember_created_file = function ($file_path) use (&$created_files) { if (is_string($file_path) && str_starts_with($file_path, \NeoReplace\NeoGlobal\uploads_dir() . "/")) { $created_files[] = $file_path; } };

        try {
            $handled_upload = wp_handle_upload($uploaded_file, ["test_form" => false]);
            if (isset($handled_upload["error"])) { \NeoReplace\NeoGlobal\throw_global_exception($handled_upload["error"], status_code: 400); }
            $handled_upload_path = $handled_upload["file"]; $remember_created_file($handled_upload_path);
            if (!\NeoReplace\NeoGlobal\fs_copy($handled_upload_path, $target_temp_path_absolute)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Could not copy uploaded file.", "Hochgeladene Datei konnte nicht kopiert werden."), status_code: 500); }
            $remember_created_file($target_temp_path_absolute);
            if ($source_uploaded_file !== null) { $handled_source_upload = wp_handle_upload($source_uploaded_file, ["test_form" => false]); if (isset($handled_source_upload["error"])) { \NeoReplace\NeoGlobal\throw_global_exception($handled_source_upload["error"], status_code: 400); } $handled_source_upload_path = $handled_source_upload["file"]; $remember_created_file($handled_source_upload_path); if (!\NeoReplace\NeoGlobal\fs_copy($handled_source_upload_path, $source_temp_path_absolute)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Could not copy source file.", "Quelldatei konnte nicht kopiert werden."), status_code: 500); } $remember_created_file($source_temp_path_absolute); }
            foreach ($old_paths as $old_path_rel) { $old_path_absolute = \NeoReplace\NeoGlobal\uploads_dir() . "/" . $old_path_rel; if (\NeoReplace\NeoGlobal\fs_is_file($old_path_absolute)) { $old_files_touched = true; if (!\NeoReplace\NeoGlobal\fs_unlink($old_path_absolute)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Could not delete old media file.", "Alte Mediendatei konnte nicht gelöscht werden.") . " " . $old_path_absolute, status_code: 500); } } }
            if (!\NeoReplace\NeoGlobal\fs_rename($target_temp_path_absolute, $target_path_absolute)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Could not move replacement file.", "Ersatzdatei konnte nicht verschoben werden."), status_code: 500); }
            $target_temp_path_absolute = null; $remember_created_file($target_path_absolute);
            if ($source_uploaded_file !== null) { if (!\NeoReplace\NeoGlobal\fs_rename($source_temp_path_absolute, $source_target_path_absolute)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Could not move source file.", "Quelldatei konnte nicht verschoben werden."), status_code: 500); } $source_temp_path_absolute = null; $remember_created_file($source_target_path_absolute); }
            wp_cache_delete($img_id, "post_meta");
            wp_cache_flush(); $transaction_start_success = $wpdb->query("START TRANSACTION"); if ($transaction_start_success === false) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Could not start database transaction.", "Datenbank-Transaktion konnte nicht gestartet werden.") . " " . ($wpdb->last_error ?: "-"), status_code: 500); } $transaction_started = true; /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. START TRANSACTION is used to have the option, in case the SQL query fails, to completely roll it back and avoid leaving any incomplete changes in the database. */
            $metadata_mime_update_success = $wpdb->update($wpdb->posts, ["post_mime_type" => $type_check["type"]], ["ID" => $img_id]); if ($metadata_mime_update_success === false) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Error while preparing attachment metadata.", "Fehler beim Vorbereiten der Attachment-Metadaten.") . " " . ($wpdb->last_error ?: "-"), status_code: 500); } clean_post_cache($img_id); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The attachment MIME type is updated early inside the same transaction because WordPress reads it while generating attachment metadata. */
            $new_metadata = wp_generate_attachment_metadata($img_id, $target_path_absolute); if (!is_array($new_metadata)) { $new_metadata = []; } if ($source_target_path_rel !== null) { $new_metadata["source_image"] = basename($source_target_path_rel); }

            $new_paths = [$target_path_rel];
            $new_metadata_file_path_rel = !empty($new_metadata["file"]) ? $replace_validate_metadata_path($new_metadata["file"], ["imgId" => $img_id, "source" => "new-metadata-file"]) : null; if ($new_metadata_file_path_rel !== null) { $new_paths[] = $new_metadata_file_path_rel; }
            $new_dir_rel = dirname($new_metadata_file_path_rel ?? $target_path_rel); if ($new_dir_rel === ".") { $new_dir_rel = ""; }
            $new_original_image_file = !empty($new_metadata["original_image"]) ? $replace_validate_metadata_path($new_metadata["original_image"], ["imgId" => $img_id, "source" => "new-metadata-original-image"]) : null; if ($new_original_image_file !== null) { $new_paths[] = \NeoReplace\NeoGlobal\path_join_rel($new_dir_rel, $new_original_image_file); }
            if ($source_target_path_rel !== null) { $new_paths[] = $source_target_path_rel; }
            foreach ($replace_collect_size_paths($target_path_rel, $new_metadata, $img_id) as $size) { $new_paths[] = $size["path_rel"]; }
            foreach (\NeoReplace\NeoGlobal\array_unique_better($new_paths) as $new_path_rel) { $remember_created_file(\NeoReplace\NeoGlobal\uploads_dir() . "/" . $new_path_rel); }

            $old_sizes = $replace_collect_size_paths($old_attached_path_rel, $old_metadata, $img_id); $new_sizes = $replace_collect_size_paths($target_path_rel, $new_metadata, $img_id); $mapping_by_old_path = [];
            foreach ($old_paths as $old_path_rel) { $mapping_by_old_path[$old_path_rel] = $target_path_rel; }
            if (isset($old_companion_paths["source_image"]) && $source_target_path_rel !== null) { $mapping_by_old_path[$old_companion_paths["source_image"]] = $source_target_path_rel; }
            if ($old_original_image_path_rel !== null) { $mapping_by_old_path[$old_original_image_path_rel] = $target_path_rel; }
            $mapping_by_old_path[$old_attached_path_rel] = $target_path_rel;

            foreach ($old_sizes as $old_size_name => $old_size) {
                if (isset($new_sizes[$old_size_name])) { $mapping_by_old_path[$old_size["path_rel"]] = $new_sizes[$old_size_name]["path_rel"]; continue; }
                foreach ($new_sizes as $new_size) { if ($new_size["width"] === $old_size["width"] && $new_size["height"] === $old_size["height"]) { $mapping_by_old_path[$old_size["path_rel"]] = $new_size["path_rel"]; continue 2; } }
                $mapping_by_old_path[$old_size["path_rel"]] = $target_path_rel;
                if (!(count($new_sizes) > 0 && $old_size["width"] > 0 && $old_size["height"] > 0)) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "Could not compare old and new image sizes to find the best match. Falling back to main file for old size: " . $old_size["path_rel"]); continue; }
                $old_area = max(1, $old_size["width"] * $old_size["height"]); $old_ratio = $old_size["width"] / max(1, $old_size["height"]); $best_path_rel = $target_path_rel; $best_score = PHP_FLOAT_MAX;
                foreach ($new_sizes as $new_size) { $new_area = max(1, $new_size["width"] * $new_size["height"]); $new_ratio = $new_size["width"] / max(1, $new_size["height"]); $score = abs($new_area - $old_area) / $old_area + abs($new_ratio - $old_ratio); if ($score < $best_score) { $best_score = $score; $best_path_rel = $new_size["path_rel"]; } }
                $mapping_by_old_path[$old_size["path_rel"]] = $best_path_rel;
            }
            \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace size mapping: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldSizes" => $old_sizes, "newSizes" => $new_sizes, "mappingByOldPath" => $mapping_by_old_path])));

            $urls_to_replace = [];
            foreach ($mapping_by_old_path as $old_path_rel => $new_path_rel) { if ($old_path_rel !== $new_path_rel) { $urls_to_replace[] = ["old_url" => \NeoReplace\NeoGlobal\uploads_url() . "/" . $old_path_rel, "new_url" => \NeoReplace\NeoGlobal\uploads_url() . "/" . $new_path_rel]; } }
            $urls_to_replace = \NeoReplace\NeoGlobal\array_unique_better($urls_to_replace);
            wp_cache_delete($img_id, "post_meta");
            wp_cache_flush();
            $error_string = null;
            update_attached_file($img_id, $target_path_absolute);  if (get_post_meta($img_id, "_wp_attached_file", true) !== $target_path_rel) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Error while updating attached file.", "Fehler beim Aktualisieren der Attachment-Datei."),           status_code: 500); }
            wp_update_attachment_metadata($img_id, $new_metadata); $saved_metadata = \NeoReplace\NeoGlobal\post_meta($img_id, "_wp_attachment_metadata"); if ($saved_metadata != $new_metadata && !($new_metadata === [] && $saved_metadata === null)) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Error while updating attachment metadata.", "Fehler beim Aktualisieren der Attachment-Metadaten."), status_code: 500); }
            if ($old_backup_sizes !== []) { \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace backup sizes deleted: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldBackupSizes" => $old_backup_sizes]))); delete_post_meta($img_id, "_wp_attachment_backup_sizes"); if (\NeoReplace\NeoGlobal\post_meta($img_id, "_wp_attachment_backup_sizes") !== null) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Error while deleting old attachment backup sizes.", "Fehler beim Löschen alter Attachment-Backup-Sizes."), status_code: 500); } }
            $post_updates = ["post_mime_type" => $type_check["type"], "post_modified" => \NeoReplace\NeoGlobal\wp_date_string(), "post_modified_gmt" => \NeoReplace\NeoGlobal\utc_date_string()];
            if ($update_upload_date) { \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace upload date: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldUploadDate" => get_post($img_id)->post_date, "newUploadDate" => \NeoReplace\NeoGlobal\wp_date_string()]))); $post_updates["post_date"] = \NeoReplace\NeoGlobal\wp_date_string(); $post_updates["post_date_gmt"] = \NeoReplace\NeoGlobal\utc_date_string(); }
            if ($use_uploaded_filename) {
                $new_title = sanitize_text_field($get_param("clean-uploaded-title"));
                if ($new_title === "") { $new_title = trim(\NeoReplace\NeoGlobal\preg_replace_better("/[-_]+/", " ", pathinfo(sanitize_file_name($uploaded_file["name"]), PATHINFO_FILENAME))); }
                if ($new_title === "") { $new_title = "file"; }
                $new_slug_input = sanitize_title($get_param("clean-uploaded-slug")); if ($new_slug_input === "") { $new_slug_input = sanitize_title(pathinfo(sanitize_file_name($uploaded_file["name"]), PATHINFO_FILENAME)); }
                $new_slug = wp_unique_post_slug($new_slug_input, $img_id, get_post_field("post_status", $img_id), get_post_field("post_type", $img_id), get_post_field("post_parent", $img_id));
                \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace title: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldTitle" => get_post_field("post_title", $img_id, context: "raw"), "newTitle" => $new_title])));
                \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace slug: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldSlug" => get_post_field("post_name", $img_id, context: "raw"), "newInput" => $new_slug_input, "newSlug" => $new_slug])));
                $post_updates["post_title"] = $new_title; $post_updates["post_name"] = $new_slug;
            }
            if ($use_uploaded_filename || $old_extension !== $new_extension || $old_attached_path_rel !== $target_path_rel) { $post_updates["guid"] = \NeoReplace\NeoGlobal\uploads_url() . "/" . $target_path_rel; }
            $post_update_success = $wpdb->update($wpdb->posts, $post_updates, ["ID" => $img_id]); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The attachment post fields are updated directly because WordPress has no helper for changing only these fields without touching user-edited media text. */
            if ($post_update_success === false) { $error_string = \NeoReplace\NeoGlobal\neo__("Error while updating attachment post.", "Fehler beim Aktualisieren des Attachment-Posts.") . " " . $wpdb->last_error; }
            if ($error_string === null) { \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace URLs: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "count" => count($urls_to_replace), "urls" => $urls_to_replace]))); $error_string = \NeoReplace\NeoGlobal\replace_media_urls_in_db($urls_to_replace); wp_cache_delete($img_id, "post_meta"); }
            if ($error_string !== null) { \NeoReplace\NeoGlobal\throw_global_exception($error_string, status_code: 500); }
            $transaction_commit_success = $wpdb->query("COMMIT"); if ($transaction_commit_success === false) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Could not commit database transaction.", "Datenbank-Transaktion konnte nicht übernommen werden.") . " " . ($wpdb->last_error ?: "-"), status_code: 500); } $transaction_started = false; $replace_success = true; /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. COMMIT is used to store the changes for the current transaction. */
            wp_cache_delete($img_id, "post_meta");
        } catch (\Throwable $e) {
            wp_cache_delete($img_id, "post_meta");
            if ($transaction_started) { $wpdb->query("ROLLBACK"); $transaction_started = false; } wp_cache_flush(); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. ROLLBACK is used to roll back all changes of the current transaction in case of an error. */

            foreach (array_reverse(\NeoReplace\NeoGlobal\array_unique_better($created_files)) as $created_file) {
                if (!is_string($created_file) || !str_starts_with($created_file, \NeoReplace\NeoGlobal\uploads_dir() . "/")) { continue; }
                if (\NeoReplace\NeoGlobal\fs_is_file($created_file) && !\NeoReplace\NeoGlobal\fs_unlink($created_file)) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace rollback could not delete created file: " . $created_file); }
            }

            if ($old_files_touched) {
                foreach (array_reverse($backup_files) as $backup_file) {
                    if (!\NeoReplace\NeoGlobal\fs_is_file($backup_file["backup"])) { continue; }
                    \NeoReplace\NeoGlobal\mkdir_better(dirname($backup_file["original"]));
                    if (\NeoReplace\NeoGlobal\fs_file_exists($backup_file["original"]) && !\NeoReplace\NeoGlobal\fs_unlink($backup_file["original"])) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace rollback could not remove created file before restore: " . $backup_file["original"]); continue; }
                    if (!\NeoReplace\NeoGlobal\fs_rename($backup_file["backup"], $backup_file["original"]) && !(\NeoReplace\NeoGlobal\fs_copy($backup_file["backup"], $backup_file["original"]) && \NeoReplace\NeoGlobal\fs_unlink($backup_file["backup"]))) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace rollback could not restore file: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive($backup_file))); }
                }
            }
            throw $e;
        } finally {
            if ($handled_upload_path !== null && \NeoReplace\NeoGlobal\fs_file_exists($handled_upload_path) && !($replace_success && $handled_upload_path === $target_path_absolute)) { \NeoReplace\NeoGlobal\fs_unlink($handled_upload_path); }
            if ($handled_source_upload_path !== null && \NeoReplace\NeoGlobal\fs_file_exists($handled_source_upload_path) && !($replace_success && $handled_source_upload_path === $source_target_path_absolute)) { \NeoReplace\NeoGlobal\fs_unlink($handled_source_upload_path); }
            if ($target_temp_path_absolute  !== null && \NeoReplace\NeoGlobal\fs_file_exists($target_temp_path_absolute)) { \NeoReplace\NeoGlobal\fs_unlink($target_temp_path_absolute); }
            if ($source_temp_path_absolute  !== null && \NeoReplace\NeoGlobal\fs_file_exists($source_temp_path_absolute)) { \NeoReplace\NeoGlobal\fs_unlink($source_temp_path_absolute); }
            if ($replace_success || !$old_files_touched) { \NeoReplace\NeoGlobal\delete_all($backup_dir); } else { \NeoReplace\NeoGlobal\remove_empty_directories($backup_dir); }
        }

        $redirect_entries = array_map(function ($url) { return ["source" => \NeoReplace\NeoGlobal\make_internal_url_relative_to_uploads($url["old_url"]), "target" => \NeoReplace\NeoGlobal\make_internal_url_relative_to_uploads($url["new_url"])]; }, $urls_to_replace);
        try { if (count($redirect_entries) > 0) { \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoRedirect\interface_add_neo_rename_redirects_20250914')($redirect_entries); } } catch (\Throwable $e) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace post-commit redirect failed: "                . $e->getMessage()); }
        try { \NeoReplace\NeoGlobal\flush_all_third_party_caches(); }                                                                                                         catch (\Throwable $e) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace post-commit third-party cache flush failed: " . $e->getMessage()); }
        try { wp_cache_flush(); }                                                                                                                                  catch (\Throwable $e) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace post-commit wp cache flush failed: "          . $e->getMessage()); }
        try { \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoImageCachebust\interface_update_last_image_cachebust_change_date_20260303')(); }                                   catch (\Throwable $e) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace post-commit image cachebust failed: "         . $e->getMessage()); }
        try { \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoOptimize\interface_regenerate_image_20251030')(wp_get_attachment_url($img_id)); }                                  catch (\Throwable $e) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace post-commit neoOptimize regenerate failed: "  . $e->getMessage()); }
        try { clean_post_cache($img_id); }                                                                                                                         catch (\Throwable $e) { \NeoReplace\NeoGlobal\global_warn_with_module_name("neo-replace", "neoReplace post-commit clean post cache failed: "        . $e->getMessage()); }
        return ["imgUrl" => \NeoReplace\NeoGlobal\percent_encode_invalid_utf8_url_bytes(wp_get_attachment_url($img_id)), "thumbnailUrl" => \NeoReplace\NeoGlobal\percent_encode_invalid_utf8_url_bytes(wp_get_attachment_image_url($img_id, "thumbnail") ?: wp_get_attachment_url($img_id)), "title" => get_post_field("post_title", $img_id, context: "raw"), "slug" => get_post_field("post_name", $img_id, context: "raw"), "pathRel" => \NeoReplace\NeoGlobal\percent_encode_invalid_utf8_url_bytes(\NeoReplace\NeoGlobal\str_replace_start(\NeoReplace\NeoGlobal\uploads_dir() . "/", "", get_attached_file($img_id))), "mimeType" => get_post_mime_type($img_id), "modifiedDate" => get_post($img_id)->post_modified, "uploadDate" => get_post($img_id)->post_date];
    });
});

\NeoReplace\NeoGlobal\register_rest_endpoint("/wp-json/neo/replace-info", "POST", fn () => \NeoReplace\NeoGlobal\current_user_can__neo_replace(), function ($get_param) {
    $img_url = \NeoReplace\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("img-url")); $img_id = attachment_url_to_postid(\NeoReplace\NeoGlobal\remove_all_query_params($img_url));
    if ($img_id === 0) { \NeoReplace\NeoGlobal\throw_global_exception(\NeoReplace\NeoGlobal\neo__("Media file not found.", "Mediendatei nicht gefunden."), status_code: 404); }
    $img_url = wp_get_attachment_url($img_id);

    $file_path = get_attached_file($img_id); $metadata = \NeoReplace\NeoGlobal\post_meta($img_id, "_wp_attachment_metadata"); if (!is_array($metadata)) { $metadata = []; }
    $response = ["imgUrl" => \NeoReplace\NeoGlobal\percent_encode_invalid_utf8_url_bytes(wp_get_attachment_url($img_id)), "title" => get_post_field("post_title", $img_id, context: "raw"), "slug" => get_post_field("post_name", $img_id, context: "raw"), "pathRel" => $file_path === false ? null : \NeoReplace\NeoGlobal\percent_encode_invalid_utf8_url_bytes(\NeoReplace\NeoGlobal\str_replace_start(\NeoReplace\NeoGlobal\uploads_dir() . "/", "", $file_path)), "mimeType" => get_post_mime_type($img_id), "fileSize" => $file_path !== false && \NeoReplace\NeoGlobal\fs_file_exists($file_path) ? \NeoReplace\NeoGlobal\fs_filesize($file_path) : null, "width" => $metadata["width"] ?? null, "height" => $metadata["height"] ?? null]; $usage_log_count = null;

    \NeoReplace\NeoGlobal\global_log_with_module_name("neo-replace", "Replace dialog opened: " . \NeoReplace\NeoGlobal\json_encode_better(\NeoReplace\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "imgUrl" => $img_url, "pathRel" => $response["pathRel"], "mimeType" => $response["mimeType"], "fileSize" => $response["fileSize"], "width" => $response["width"], "height" => $response["height"], "usageCount" => $usage_log_count])));
    return $response;
});
