<?php
namespace NeoRename\NeoRename; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function rename_media_file($media_id, $new_image_path_rel) {
    global $wpdb;

    $new_image_path_rel = ltrim($new_image_path_rel, "/");
    if (empty($new_image_path_rel)) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Path cannot be empty.", "Pfad darf nicht leer sein."), status_code: 400); }
    if (str_contains($new_image_path_rel, "../")) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Invalid path.", "Ungültiger Pfad."), status_code: 403); }
    if (!wp_get_attachment_url($media_id)) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Image does not exist.", "Bild existiert nicht."), status_code: 404); }
    if (strlen($new_image_path_rel) > 255) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Filename is too long.", "Dateiname ist zu lang."), status_code: 400); }
    if (empty(pathinfo($new_image_path_rel, PATHINFO_FILENAME))) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Filename cannot be empty.", "Dateiname darf nicht leer sein."), status_code: 400); }
    if (empty(pathinfo($new_image_path_rel, PATHINFO_EXTENSION))) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Filename must include an extension.", "Dateiname muss eine Dateiendung enthalten."), status_code: 400); }

    $old_image_path_absolute = get_attached_file($media_id);
    if ($old_image_path_absolute === false) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Image file path could not be determined.", "Der Bild-Dateipfad konnte nicht ermittelt werden."), status_code: 500); }
    if (!str_starts_with($old_image_path_absolute, \NeoRename\NeoGlobal\uploads_dir() . "/")) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Image is not in the uploads folder.", "Bild befindet sich nicht im Uploads-Ordner."), status_code: 403); }
    if (!\NeoRename\NeoGlobal\fs_is_file($old_image_path_absolute)) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Image file does not exist on disk.", "Bilddatei existiert nicht auf der Festplatte.") . " " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["postID" => $media_id, "oldPathAbsolute" => $old_image_path_absolute])), status_code: 404); }
    $old_image_path_rel = str_replace(\NeoRename\NeoGlobal\uploads_dir() . "/", "", $old_image_path_absolute);

    $old_extension = strtolower(pathinfo($old_image_path_absolute, PATHINFO_EXTENSION)); $new_extension = strtolower(pathinfo($new_image_path_rel, PATHINFO_EXTENSION));
    $old_extension_normalized = $old_extension === "jpeg" ? "jpg" : $old_extension; $new_extension_normalized = $new_extension === "jpeg" ? "jpg" : $new_extension;
    if ($new_extension_normalized !== $old_extension_normalized) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Changing the file extension is not allowed.", "Das Ändern der Dateiendung ist nicht erlaubt."), status_code: 400); }
    $blocked_executable_extensions = ["phtml", "pht", "phtm", "phar", "phps", "shtml", "cgi", "pl", "asp", "aspx", "jsp"];
    $new_filename_extension_parts = explode(".", strtolower(basename($new_image_path_rel))); array_shift($new_filename_extension_parts);
    foreach ($new_filename_extension_parts as $new_filename_extension_part) {
        if ($new_image_path_rel !== $old_image_path_rel && (\NeoRename\NeoGlobal\preg_match_better("/^php\\d*$/", $new_filename_extension_part) || in_array($new_filename_extension_part, $blocked_executable_extensions, true))) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("This file extension is not allowed.", "Diese Dateiendung ist nicht erlaubt."), status_code: 400); }
    }

    $old_image_dir      = dirname($old_image_path_absolute);
    $old_image_dir_rel  = ltrim(str_replace(\NeoRename\NeoGlobal\uploads_dir(), "", $old_image_dir), "/");

    $new_image_dir_rel = dirname($new_image_path_rel); if ($new_image_dir_rel === ".") { $new_image_dir_rel = ""; }
    $new_image_dir = untrailingslashit(\NeoRename\NeoGlobal\uploads_dir() . "/" . ltrim($new_image_dir_rel, "/"));
    if (!str_starts_with($new_image_dir, \NeoRename\NeoGlobal\uploads_dir())) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Target outside uploads.", "Ziel außerhalb des Upload-Ordners."), status_code: 403); }
    \NeoRename\NeoGlobal\mkdir_better($new_image_dir);

    if ($new_image_path_rel === $old_image_path_rel) {
        \NeoRename\NeoGlobal\global_log_with_module_name("neo-rename", "No file renaming necessary: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["postID" => $media_id, "oldPathRel" => $old_image_path_rel, "newPathRel" => $new_image_path_rel, "changes" => false])));
        return;
    }

    if (\NeoRename\NeoGlobal\fs_file_exists(\NeoRename\NeoGlobal\uploads_dir() . "/" . $new_image_path_rel)) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Target file already exists.", "Zieldatei existiert bereits.") . " " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["postID" => $media_id, "oldPathRel" => $old_image_path_rel, "newPathRel" => $new_image_path_rel])), status_code: 409); }

    \NeoRename\NeoGlobal\global_log_with_module_name("neo-rename", "Rename file: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "oldPathRel" => $old_image_path_rel, "newPathRel" => $new_image_path_rel, "changes" => true])));

    $rel_paths_to_replace = [];
    $rel_path_mapping_by_old_path = [];
    $add_rel_path_mapping = function ($old_rel_path, $new_rel_path) use (&$rel_paths_to_replace, &$rel_path_mapping_by_old_path) {
        if (isset($rel_path_mapping_by_old_path[$old_rel_path])) { return $rel_path_mapping_by_old_path[$old_rel_path]; }
        $rel_path_mapping_by_old_path[$old_rel_path] = $new_rel_path;
        $rel_paths_to_replace[] = ["old_rel_path" => $old_rel_path, "new_rel_path" => $new_rel_path];
        return $new_rel_path;
    };

    $old_metadata = \NeoRename\NeoGlobal\post_meta($media_id, "_wp_attachment_metadata");
    if (!is_array($old_metadata)) { $old_metadata = []; }
    $old_backup_sizes = \NeoRename\NeoGlobal\post_meta($media_id, "_wp_attachment_backup_sizes");
    if (!is_array($old_backup_sizes)) { $old_backup_sizes = []; }
    $old_metadata_dir_rel = dirname($old_metadata["file"] ?? $old_image_path_rel); if ($old_metadata_dir_rel === ".") { $old_metadata_dir_rel = ""; }
    $new_image_path_rel_extension = pathinfo($new_image_path_rel, PATHINFO_EXTENSION);
    $new_image_path_rel_base = \NeoRename\NeoGlobal\preg_replace_better('/\.' . preg_quote($new_image_path_rel_extension, '/') . '$/', '', $new_image_path_rel);

    $add_rel_path_mapping($old_image_path_rel, $new_image_path_rel);

    foreach ($old_metadata["sizes"] ?? [] as $size) {
        $old_size_path_rel = ($old_image_dir_rel === "" ? "" : $old_image_dir_rel . "/") . $size["file"];
        $size_filename_extension = pathinfo($old_size_path_rel, PATHINFO_EXTENSION);
        $new_size_rel_path = $new_image_path_rel_base . "-{$size['width']}x{$size['height']}." . $size_filename_extension;
        $add_rel_path_mapping($old_size_path_rel, $new_size_rel_path);
    }

    if (isset($old_metadata["original_image"])) {
        $original_filename_extension = pathinfo($old_metadata["original_image"], PATHINFO_EXTENSION);
        $new_original_path_rel = $new_image_path_rel_base . "-original." . $original_filename_extension;
        $add_rel_path_mapping(($old_image_dir_rel === "" ? "" : $old_image_dir_rel . "/") . $old_metadata["original_image"], $new_original_path_rel);
    }

    $companion_rel_path_mappings_by_key = [];
    foreach (["source_image" => "source", "animated_video" => "animated-video", "animated_video_poster" => "animated-video-poster"] as $companion_key => $collision_suffix) {
        if (empty($old_metadata[$companion_key]) || !is_string($old_metadata[$companion_key])) { continue; }
        if (str_contains($old_metadata[$companion_key], "/") || str_contains($old_metadata[$companion_key], "\0")) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename skipped invalid companion filename: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "companionKey" => $companion_key, "file" => $old_metadata[$companion_key]]))); continue; }
        $old_companion_path_rel = ($old_metadata_dir_rel === "" ? "" : $old_metadata_dir_rel . "/") . $old_metadata[$companion_key];
        if (!\NeoRename\NeoGlobal\fs_is_file(\NeoRename\NeoGlobal\uploads_dir() . "/" . $old_companion_path_rel)) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename skipped missing companion file: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "companionKey" => $companion_key, "oldPathRel" => $old_companion_path_rel]))); continue; }
        $companion_extension = pathinfo($old_metadata[$companion_key], PATHINFO_EXTENSION);
        $new_companion_path_rel = $new_image_path_rel_base . ($companion_extension === "" ? "" : "." . $companion_extension);
        if (($new_companion_path_rel !== $old_companion_path_rel && \NeoRename\NeoGlobal\fs_file_exists(\NeoRename\NeoGlobal\uploads_dir() . "/" . $new_companion_path_rel)) || in_array($new_companion_path_rel, $rel_path_mapping_by_old_path, true)) { $new_companion_path_rel = $new_image_path_rel_base . "-" . $collision_suffix . ($companion_extension === "" ? "" : "." . $companion_extension); }
        $companion_dedupe_index = 1; $new_companion_filename_base = pathinfo($new_companion_path_rel, PATHINFO_FILENAME);
        while (($new_companion_path_rel !== $old_companion_path_rel && \NeoRename\NeoGlobal\fs_file_exists(\NeoRename\NeoGlobal\uploads_dir() . "/" . $new_companion_path_rel)) || in_array($new_companion_path_rel, $rel_path_mapping_by_old_path, true)) { $companion_dedupe_index++; $new_companion_path_rel = ($new_image_dir_rel === "" ? "" : $new_image_dir_rel . "/") . $new_companion_filename_base . "-" . $companion_dedupe_index . ($companion_extension === "" ? "" : "." . $companion_extension); }
        $companion_rel_path_mappings_by_key[$companion_key] = $add_rel_path_mapping($old_companion_path_rel, $new_companion_path_rel);
    }

    $backup_rel_path_mappings_by_key = [];
    foreach ($old_backup_sizes as $backup_size_name => $backup_size) {
        if (empty($backup_size["file"])) { continue; }
        if (!is_string($backup_size["file"])) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename skipped invalid backup size path type: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "backupSize" => $backup_size_name, "file" => $backup_size["file"]]))); continue; }

        if (str_starts_with($backup_size["file"], "/") || str_contains($backup_size["file"], "\0")) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename skipped invalid backup size path: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "backupSize" => $backup_size_name, "file" => $backup_size["file"]]))); continue; }
        foreach (explode("/", $backup_size["file"]) as $backup_path_part) { if ($backup_path_part === "" || $backup_path_part === "." || $backup_path_part === "..") { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename skipped invalid backup size path segment: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "backupSize" => $backup_size_name, "file" => $backup_size["file"]]))); continue 2; } }
        $old_backup_path_rel = str_contains($backup_size["file"], "/") ? $backup_size["file"] : (($old_metadata_dir_rel === "" ? "" : $old_metadata_dir_rel . "/") . $backup_size["file"]);

        if (!\NeoRename\NeoGlobal\fs_file_exists(\NeoRename\NeoGlobal\uploads_dir() . "/" . $old_backup_path_rel)) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename skipped missing backup size file: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "backupSize" => $backup_size_name, "oldPathRel" => $old_backup_path_rel]))); continue; }

        $new_backup_path_rel = $rel_path_mapping_by_old_path[$old_backup_path_rel] ?? null;
        if ($new_backup_path_rel === null) {
            $old_backup_stem = pathinfo($old_backup_path_rel, PATHINFO_FILENAME); $old_backup_extension = pathinfo($old_backup_path_rel, PATHINFO_EXTENSION); $best_old_stem = ""; $new_backup_stem = "";
            foreach ($rel_path_mapping_by_old_path as $mapped_old_rel_path => $mapped_new_rel_path) { $mapped_old_stem = pathinfo($mapped_old_rel_path, PATHINFO_FILENAME); if ($mapped_old_stem !== "" && str_starts_with($old_backup_stem, $mapped_old_stem) && strlen($mapped_old_stem) > strlen($best_old_stem)) { $best_old_stem = $mapped_old_stem; $new_backup_stem = pathinfo($mapped_new_rel_path, PATHINFO_FILENAME) . substr($old_backup_stem, strlen($mapped_old_stem)); } }
            $fallback_suffix = sanitize_file_name((string) $backup_size_name); if ($fallback_suffix === "") { $fallback_suffix = "backup"; }
            if ($new_backup_stem === "") { $new_backup_stem = pathinfo($new_image_path_rel, PATHINFO_FILENAME) . "-" . $fallback_suffix; }
            $new_backup_path_rel = ($new_image_dir_rel === "" ? "" : $new_image_dir_rel . "/") . $new_backup_stem . ($old_backup_extension === "" ? "" : "." . $old_backup_extension);
            $planned_dedupe_index = 1; $new_backup_filename_base = pathinfo($new_backup_path_rel, PATHINFO_FILENAME);
            while (in_array($new_backup_path_rel, $rel_path_mapping_by_old_path, true) || \NeoRename\NeoGlobal\fs_file_exists(\NeoRename\NeoGlobal\uploads_dir() . "/" . $new_backup_path_rel)) { $planned_dedupe_index++; $new_backup_path_rel = ($new_image_dir_rel === "" ? "" : $new_image_dir_rel . "/") . $new_backup_filename_base . "-" . $planned_dedupe_index . ($old_backup_extension === "" ? "" : "." . $old_backup_extension); }
        }

        $new_backup_path_rel = $add_rel_path_mapping($old_backup_path_rel, $new_backup_path_rel);
        $backup_rel_path_mappings_by_key[$backup_size_name] = ["old_file" => $backup_size["file"], "old_rel_path" => $old_backup_path_rel, "new_rel_path" => $new_backup_path_rel];
    }
    if (count($backup_rel_path_mappings_by_key) > 0) { \NeoRename\NeoGlobal\global_log_with_module_name("neo-rename", "Rename backup sizes: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "backupMappings" => $backup_rel_path_mappings_by_key]))); }

    foreach (["webp", "avif"] as $alternative_extension) {
        foreach ($rel_paths_to_replace as $rel_path) {
            $old_rel_path = \NeoRename\NeoGlobal\preg_replace_better('/\.[^.]+$/', '.' . $alternative_extension, $rel_path["old_rel_path"]);
            $new_rel_path = \NeoRename\NeoGlobal\preg_replace_better('/\.[^.]+$/', '.' . $alternative_extension, $rel_path["new_rel_path"]);
            if (\NeoRename\NeoGlobal\fs_file_exists(\NeoRename\NeoGlobal\uploads_dir() . "/" . $old_rel_path) && attachment_url_to_postid(\NeoRename\NeoGlobal\uploads_url() . "/" . $old_rel_path) === 0) {
                $add_rel_path_mapping($old_rel_path, $new_rel_path);
            }

            $old_rel_path = $rel_path["old_rel_path"] . "." . $alternative_extension;
            $new_rel_path = $rel_path["new_rel_path"] . "." . $alternative_extension;
            if (\NeoRename\NeoGlobal\fs_file_exists(\NeoRename\NeoGlobal\uploads_dir() . "/" . $old_rel_path) && attachment_url_to_postid(\NeoRename\NeoGlobal\uploads_url() . "/" . $old_rel_path) === 0) {
                $add_rel_path_mapping($old_rel_path, $new_rel_path);
            }
        }
    }

    $filepaths_to_rename = array_map(fn ($rel_path) => ["old_filepath" => \NeoRename\NeoGlobal\uploads_dir() . "/" . $rel_path["old_rel_path"], "new_filepath" => \NeoRename\NeoGlobal\uploads_dir() . "/" . $rel_path["new_rel_path"], "is_main_file" => $rel_path["old_rel_path"] === $old_image_path_rel], $rel_paths_to_replace);
    $urls_to_replace     = array_map(fn ($rel_path) => ["old_url"      => \NeoRename\NeoGlobal\uploads_url() . "/" . $rel_path["old_rel_path"], "new_url"      => \NeoRename\NeoGlobal\uploads_url() . "/" . $rel_path["new_rel_path"]], $rel_paths_to_replace);

    wp_cache_delete($media_id, "post_meta");
    wp_cache_flush();
    $transaction_start_success = $wpdb->query("START TRANSACTION"); if ($transaction_start_success === false) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Could not start database transaction.", "Datenbank-Transaktion konnte nicht gestartet werden.") . " " . ($wpdb->last_error ?: "-"), status_code: 500); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. START TRANSACTION is used to have the option, in case the SQL query fails, to completely roll it back and avoid leaving any incomplete changes in the database. */
    $error_string = \NeoRename\NeoGlobal\replace_media_urls_in_db($urls_to_replace);
    wp_cache_delete($media_id, "post_meta");
    wp_cache_flush();
    if ($error_string === null && \NeoRename\NeoGlobal\post_meta($media_id, "_wp_attached_file") !== $new_image_path_rel) { $error_string = \NeoRename\NeoGlobal\neo__("The attachment file path was not updated in the database.", "Der Dateipfad des Anhangs wurde nicht in der Datenbank aktualisiert."); }

    $new_metadata = unserialize(serialize($old_metadata));
    if (isset($new_metadata["file"])) { $new_metadata["file"] = $new_image_path_rel; }
    if (isset($new_original_path_rel)) { $new_metadata["original_image"] = basename($new_original_path_rel); }
    foreach ($companion_rel_path_mappings_by_key as $companion_key => $new_companion_path_rel) { $new_metadata[$companion_key] = basename($new_companion_path_rel); }
    if (isset($new_metadata["sizes"])) {
        foreach ($new_metadata["sizes"] as $size_name => $size_info) {
            if (!isset($size_info["file"])) { continue; }
            $old_size_info_path_rel = ($old_image_dir_rel === "" ? "" : $old_image_dir_rel . "/") . $size_info["file"];
            if (isset($rel_path_mapping_by_old_path[$old_size_info_path_rel])) { $new_metadata["sizes"][$size_name]["file"] = basename($rel_path_mapping_by_old_path[$old_size_info_path_rel]); }
        }
    }

    if (\NeoRename\NeoGlobal\json_encode_better($new_metadata) !== \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\post_meta($media_id, "_wp_attachment_metadata"))) {
        $meta_update_success = update_post_meta($media_id, "_wp_attachment_metadata", wp_slash($new_metadata));
        $saved_metadata = \NeoRename\NeoGlobal\post_meta($media_id, "_wp_attachment_metadata");
        if ($saved_metadata != $new_metadata && !($new_metadata === [] && $saved_metadata === null)) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename attachment metadata differs after update: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $media_id, "updateResult" => $meta_update_success, "databaseError" => $wpdb->last_error, "expectedMetadata" => $new_metadata, "savedMetadata" => $saved_metadata]))); }
    }

    $new_backup_sizes = $old_backup_sizes;
    foreach ($backup_rel_path_mappings_by_key as $backup_size_name => $backup_mapping) {
        if (!isset($new_backup_sizes[$backup_size_name]["file"])) { continue; }
        $new_backup_sizes[$backup_size_name]["file"] = str_contains((string) $backup_mapping["old_file"], "/") ? $backup_mapping["new_rel_path"] : basename($backup_mapping["new_rel_path"]);
    }
    if ($old_backup_sizes !== [] && \NeoRename\NeoGlobal\json_encode_better($new_backup_sizes) !== \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\post_meta($media_id, "_wp_attachment_backup_sizes"))) {
        $backup_meta_update_success = update_post_meta($media_id, "_wp_attachment_backup_sizes", wp_slash($new_backup_sizes));
        if ($backup_meta_update_success === false) { $error_string = \NeoRename\NeoGlobal\neo__("Error while updating attachment backup sizes in database.", "Fehler beim Aktualisieren der Anhang-Backup-Sizes in der Datenbank."); }
    }

    $successfully_renamed_files = [];
    if ($error_string === null) {
        foreach ($filepaths_to_rename as $file_to_rename) {
            if (\NeoRename\NeoGlobal\fs_file_exists($file_to_rename["new_filepath"])) {
                $error_string = \NeoRename\NeoGlobal\neo__("Error while renaming file on disk. The target file name " . $file_to_rename["new_filepath"] . " already exists.", "Fehler beim Umbenennen der Datei auf der Festplatte. Der Ziel-Dateiname " . $file_to_rename["new_filepath"] . " existiert bereits.");
                break;
            }

            if (!\NeoRename\NeoGlobal\fs_is_writable(dirname($file_to_rename["new_filepath"]))) {
                $error_string = \NeoRename\NeoGlobal\neo__("Error while renaming file on disk. The target directory " . dirname($file_to_rename["new_filepath"]) . " is not writable. Please check the directory permissions.", "Fehler beim Umbenennen der Datei auf der Festplatte. Das Zielverzeichnis " . dirname($file_to_rename["new_filepath"]) . " ist nicht beschreibbar. Bitte überprüfe die Verzeichnisberechtigungen.");
                break;
            }

            $rename_success = \NeoRename\NeoGlobal\fs_rename($file_to_rename["old_filepath"], $file_to_rename["new_filepath"]);
            if ($rename_success) {
                $successfully_renamed_files[] = $file_to_rename;

                $directory_to_delete = dirname($file_to_rename["old_filepath"]);
                while (str_starts_with($directory_to_delete, \NeoRename\NeoGlobal\uploads_dir() . "/") && $directory_to_delete !== \NeoRename\NeoGlobal\uploads_dir()) {
                    if (count(\NeoRename\NeoGlobal\fs_scandir($directory_to_delete) ?: []) !== 2) { break; }
                    \NeoRename\NeoGlobal\fs_rmdir($directory_to_delete);
                    $directory_to_delete = dirname($directory_to_delete);
                }
            } else if ($file_to_rename["is_main_file"]) { $error_string = \NeoRename\NeoGlobal\neo__("Error while renaming main file on disk.", "Fehler beim Umbenennen der Hauptdatei auf der Festplatte.") . " " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive($file_to_rename)); break; }
            else {

            }
        }
    }

    if ($error_string === null) {
        $transaction_commit_success = $wpdb->query("COMMIT"); if ($transaction_commit_success === false) { $error_string = \NeoRename\NeoGlobal\neo__("Could not commit database transaction.", "Datenbank-Transaktion konnte nicht übernommen werden.") . " " . ($wpdb->last_error ?: "-"); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. COMMIT is used to store the changes for the current transaction. */
        wp_cache_delete($media_id, "post_meta");
        if ($error_string !== null) { $wpdb->query("ROLLBACK"); wp_cache_flush(); foreach ($successfully_renamed_files as $successfully_renamed_file) { \NeoRename\NeoGlobal\mkdir_better(dirname($successfully_renamed_file["old_filepath"])); \NeoRename\NeoGlobal\fs_rename($successfully_renamed_file["new_filepath"], $successfully_renamed_file["old_filepath"]); } \NeoRename\NeoGlobal\throw_global_exception($error_string, status_code: 500); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. ROLLBACK is used to roll back all changes of the current transaction in case of an error. */

        try { \NeoRename\NeoGlobal\flush_all_third_party_caches(); } catch (\Throwable $e) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename post-commit third-party cache flush failed: " . $e->getMessage()); }
        try { wp_cache_flush(); } catch (\Throwable $e) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename post-commit wp cache flush failed: " . $e->getMessage()); }

        try { foreach ($urls_to_replace as $url_to_replace) { \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoOptimize\interface_rename_image_20251030')($url_to_replace["old_url"], $url_to_replace["new_url"]); } } catch (\Throwable $e) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename post-commit neoOptimize rename failed: " . $e->getMessage()); }
    } else {
        foreach ($successfully_renamed_files as $successfully_renamed_file) {
            \NeoRename\NeoGlobal\mkdir_better(dirname($successfully_renamed_file["old_filepath"]));
            \NeoRename\NeoGlobal\fs_rename($successfully_renamed_file["new_filepath"], $successfully_renamed_file["old_filepath"]);
        }

        wp_cache_delete($media_id, "post_meta");
        $wpdb->query("ROLLBACK"); wp_cache_flush(); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. ROLLBACK is used to roll back all changes of the current transaction in case of an error. */

        \NeoRename\NeoGlobal\throw_global_exception($error_string, status_code: 500);
    }

    $redirect_entries = array_map(function ($url) { return ["source" => \NeoRename\NeoGlobal\make_internal_url_relative_to_uploads($url["old_url"]), "target" => \NeoRename\NeoGlobal\make_internal_url_relative_to_uploads($url["new_url"])] ; }, $urls_to_replace);
    try { \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoRedirect\interface_add_neo_rename_redirects_20250914')($redirect_entries); } catch (\Throwable $e) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename post-commit redirect failed: " . $e->getMessage()); }

    try { \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoImageCachebust\interface_update_last_image_cachebust_change_date_20260303')(); } catch (\Throwable $e) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "neoRename post-commit image cachebust failed: " . $e->getMessage()); }
}
function interface_rename_media_file_20260521($media_id, $new_image_path_rel) { return rename_media_file($media_id, $new_image_path_rel); }

function rename_update_title($img_id, $new_title) {
    global $wpdb;
    \NeoRename\NeoGlobal\global_log_with_module_name("neo-rename", "Rename title: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldTitle" => get_post_field("post_title", $img_id), "newTitle" => $new_title])));
    $success = $wpdb->update($wpdb->posts, ["post_title" => $new_title], ["ID" => $img_id], ["%s"], ["%d"]); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. The WP function would update the image’s modification date, which would result in unexpected behavior for the user, since they are only renaming the image. */
    if ($success === false) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Error while renaming title in database.", "Fehler beim Umbenennen des Titels in der Datenbank.") . " " . $wpdb->last_error, status_code: 500); }
    wp_cache_flush();
}

function rename_update_alt_text($img_id, $new_alt_text) {
    \NeoRename\NeoGlobal\global_log_with_module_name("neo-rename", "Rename alt text: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $img_id, "oldAltText" => \NeoRename\NeoGlobal\post_meta($img_id, "_wp_attachment_image_alt") ?: "", "newAltText" => $new_alt_text])));
    if ($new_alt_text === "") { delete_post_meta($img_id, "_wp_attachment_image_alt"); wp_cache_flush(); if ((\NeoRename\NeoGlobal\post_meta($img_id, "_wp_attachment_image_alt") ?: "") !== "") { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Error while deleting alt text in database.", "Fehler beim Löschen des Alt-Texts in der Datenbank."), status_code: 500); } return; }
    update_post_meta($img_id, "_wp_attachment_image_alt", wp_slash($new_alt_text)); wp_cache_flush(); if ((\NeoRename\NeoGlobal\post_meta($img_id, "_wp_attachment_image_alt") ?: "") !== $new_alt_text) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Error while updating alt text in database.", "Fehler beim Aktualisieren des Alt-Texts in der Datenbank."), status_code: 500); }
    wp_cache_flush();
}

function rename_update_slug($image_id, $new_slug_input) {
    global $wpdb;

    $old_slug = get_post_field("post_name", $image_id);
    if ($old_slug === $new_slug_input) {
        $new_slug = $old_slug;
    } else {
        $new_slug = wp_unique_post_slug($new_slug_input, $image_id, get_post_field("post_status", $image_id), get_post_field("post_type", $image_id), get_post_field("post_parent", $image_id));
    }
    \NeoRename\NeoGlobal\global_log_with_module_name("neo-rename", "Rename slug: " . \NeoRename\NeoGlobal\json_encode_better(\NeoRename\NeoGlobal\percent_encode_invalid_utf8_bytes_recursive(["imgId" => $image_id, "oldSlug" => $old_slug, "newInput" => $new_slug_input, "newSlug" => $new_slug])));

    $success = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_name = %s WHERE ID = %d", $new_slug, $image_id)); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. The WP function would update the image’s modification date, which would result in unexpected behavior for the user, since they are only renaming the image. */
    if ($success === false) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Error while renaming slug in database.", "Fehler beim Umbenennen des Slugs in der Datenbank.") . " " . $wpdb->last_error, status_code: 500); }

    wp_cache_flush();
}

\NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/rename", "POST", fn () => \NeoRename\NeoGlobal\current_user_can__neo_rename(), function ($get_param) {
    return \NeoRename\NeoGlobal\synclock_dir(\NeoRename\NeoGlobal\uploads_dir(), timeout: 20, scope: "neo-rename", callback: function () use ($get_param) {
        $old_img_url = \NeoRename\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("img-url"));
        $img_id = attachment_url_to_postid($old_img_url);
        if ($img_id === 0) { \NeoRename\NeoGlobal\throw_global_exception(\NeoRename\NeoGlobal\neo__("Image not found.", "Bild nicht gefunden."), status_code: 404); }

        $old_title    = get_post_field("post_title", $img_id, context: "raw");
        $old_path_rel = str_replace(\NeoRename\NeoGlobal\uploads_dir() . "/", "", get_attached_file($img_id));
        $old_slug     = get_post_field("post_name",  $img_id, context: "raw");
        $old_alt_text = \NeoRename\NeoGlobal\post_meta($img_id, "_wp_attachment_image_alt") ?: "";

        rename_media_file(  $img_id, \NeoRename\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("path-rel")));
        rename_update_title($img_id, $get_param("title"));
        rename_update_slug( $img_id, $get_param("slug"));
        rename_update_alt_text($img_id, $get_param("alt-text"));

        [$_, $interface_ok] = \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoRenameUndo\interface_save_rename_for_undo_20250915')($img_id, $old_path_rel, $old_title, $old_slug, $old_alt_text);
        if (!$interface_ok) { \NeoRename\NeoGlobal\global_warn_with_module_name("neo-rename", "Failed to save rename for undo for image ID " . $img_id); }

        return [
            "imgUrl"  => \NeoRename\NeoGlobal\percent_encode_invalid_utf8_url_bytes(wp_get_attachment_url($img_id)),
            "title"   => get_post_field("post_title", $img_id, context: "raw"),
            "slug"    => get_post_field("post_name",  $img_id, context: "raw"),
            "altText" => \NeoRename\NeoGlobal\post_meta($img_id, "_wp_attachment_image_alt") ?: "",
            "pathRel" => \NeoRename\NeoGlobal\percent_encode_invalid_utf8_url_bytes(str_replace(\NeoRename\NeoGlobal\uploads_dir() . "/", "", get_attached_file($img_id))),
        ];
    });
});
