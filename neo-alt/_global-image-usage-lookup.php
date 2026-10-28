<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function db_entries_image_usage_lookup($img_urls, $max_sql_time_seconds = 20, $include_content_preview = true) {
    global $wpdb;

    $img_size_urls = []; $img_id_by_img_url = [];
    foreach ($img_urls as $img_url) {
        $img_id = attachment_url_to_postid($img_url); $img_id_by_img_url[$img_url] = $img_id;
        if (!$img_id) { continue; }
        $make_img_url_relative_without_first_slash = function ($img_url) { return ltrim(str_replace(site_url(), "", $img_url), "/"); };
        $img_size_urls[$make_img_url_relative_without_first_slash($img_url)] = $img_url;
        $img_meta = wp_get_attachment_metadata($img_id);
        if (isset($img_meta["sizes"])) {
            foreach ($img_meta["sizes"] as $img_size => $img_size_meta) {
                $absolute_size_url = substr($img_url, 0, strrpos($img_url, "/")) . "/" . $img_size_meta["file"];
                $img_size_urls[$make_img_url_relative_without_first_slash($absolute_size_url)] = $img_url;
            }
        }
    }

    $img_size_urls_with_json = [];
    foreach ($img_size_urls as $relative_img_size_url => $absolute_img_url) {
        $img_url_json_escaped = trim(json_encode($relative_img_size_url), '"');
        $img_size_urls_with_json[$img_url_json_escaped] = $absolute_img_url;
    }
    $img_size_urls = array_merge($img_size_urls, $img_size_urls_with_json);
    if (empty($img_size_urls)) { return []; }

    $regexp_any_size_img_url = implode("|", array_map("preg_quote", array_keys($img_size_urls)));
    $max_regex_chunk_len = 32768;

    $regexp_chunks = [];
    $parts = explode("|", $regexp_any_size_img_url);
    $current = "";
    foreach ($parts as $part) {
        if ($current !== "" && strlen($current) + strlen($part) + 1 > $max_regex_chunk_len) {
            $regexp_chunks[] = $current; $current = $part;
        } else {
            $current .= ($current === "" ? "" : "|") . $part;
        }
    }
    if ($current !== "") { $regexp_chunks[] = $current; }

    $db_entries_using_images = [];
    foreach ($img_urls as $img_url) { $db_entries_using_images[$img_url] = []; }

    $query_start_time = microtime(true); $db_is_mariadb = str_contains(strtolower($wpdb->db_server_info()), "mariadb");
    $get_results_with_timeout = function ($query, $output = OBJECT) use ($wpdb, $max_sql_time_seconds, $query_start_time, $db_is_mariadb) {
        $remaining_time_seconds = max(0.001, $max_sql_time_seconds - (microtime(true) - $query_start_time));
        $timed_query = $db_is_mariadb ? "SET STATEMENT max_statement_time=" . sprintf("%.3F", $remaining_time_seconds) . " FOR " . $query : \NeoAlt\NeoGlobal\preg_replace_better("/^SELECT\\h+/i", "SELECT /*+ MAX_EXECUTION_TIME(" . max(1, intval(ceil($remaining_time_seconds * 1000))) . ") */ ", $query);
        $results = $wpdb->get_results($timed_query, $output); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The query is prepared by the calling code where needed and receives only a database-specific execution-time clause here. */
        return $wpdb->last_error === "" ? $results : false;
    };
    $table_should_be_skipped_for_usage_lookup = function ($table_name) use ($wpdb) { return in_array(substr($table_name, strlen($wpdb->prefix)), ["actionscheduler_actions", "actionscheduler_claims", "actionscheduler_groups", "actionscheduler_logs", "woocommerce_sessions", "wc_admin_notes", "wc_admin_note_actions", "ptk_patterns"], true); };
    $options_without_transients_sql = "option_name NOT LIKE '\\_transient\\_%' AND option_name NOT LIKE '\\_site\\_transient\\_%'";
    $option_names_skipped_for_usage_lookup = ["neo_rename_undo__list", "WpFcDeleteCacheLogs", "elementor_remote_info_library"];
    $option_names_skipped_placeholders = implode(", ", array_fill(0, count($option_names_skipped_for_usage_lookup), "%s"));
    $text_columns_by_table_name = [];
    foreach ($wpdb->get_results("SHOW TABLES", ARRAY_N) as $table) { /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
        $table_name = $table[0];
        if (strpos($table_name, $wpdb->prefix) !== 0) { continue; }
        if ($table_should_be_skipped_for_usage_lookup($table_name)) { continue; }
        foreach ($wpdb->get_results("SHOW COLUMNS FROM `$table_name`") as $column) { /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            if (!(strpos($column->Type, "char") !== false || strpos($column->Type, "text") !== false)) { continue; }
            $column_name = $column->Field;
            if ($table_name === $wpdb->posts && $column_name === "guid") { continue; }
            $text_columns_by_table_name[$table_name][] = $column_name;
        }
    }
    $img_url_file_extensions = []; $img_url_file_extensions_include_unknown = false;
    foreach (array_keys($img_size_urls) as $relative_img_size_url) { $img_url_file_extension = strtolower(pathinfo(str_replace("\\/", "/", $relative_img_size_url), PATHINFO_EXTENSION)); if ($img_url_file_extension === "") { $img_url_file_extensions_include_unknown = true; continue; } $img_url_file_extensions[] = $img_url_file_extension; }
    $img_url_file_extensions = \NeoAlt\NeoGlobal\array_unique_better($img_url_file_extensions);
    $regexp_img_url_file_extensions_filter = !$img_url_file_extensions_include_unknown && count($img_url_file_extensions) > 0 ? "(?i)\\\\.(" . implode("|", array_map("preg_quote", $img_url_file_extensions)) . ")" : null;

    foreach ($text_columns_by_table_name as $table_name => $column_names_maybe_with_img_url) {
        $already_added_rows_hashes = [];
        foreach ($column_names_maybe_with_img_url as $column_name_maybe_with_img_url) {
            if ($regexp_img_url_file_extensions_filter !== null) { $rows_with_img_url_extension = $get_results_with_timeout("SELECT 1 FROM `$table_name` WHERE `$column_name_maybe_with_img_url` REGEXP '$regexp_img_url_file_extensions_filter' LIMIT 1;"); if ($rows_with_img_url_extension === false) { return false; } if (count($rows_with_img_url_extension) === 0) { continue; } } /* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $regexp_img_url_file_extensions_filter is built from escaped file extensions above. */

            $where_parts = [];
            $args = [];
            foreach ($regexp_chunks as $chunk) {
                $where_parts[] = "`$column_name_maybe_with_img_url` REGEXP %s";
                $args[] = $chunk;
            }
            $where_regexp = "(" . implode(" OR ", $where_parts) . ")";
            $args_with_post_name_filter = array_merge(["%-autosave-v%"], $args);
            $args_with_skipped_options = array_merge($option_names_skipped_for_usage_lookup, $args);
                 if ($table_name === $wpdb->posts) {    $rows_containing_img_urls = $get_results_with_timeout($wpdb->prepare("SELECT * FROM `$table_name`                                     WHERE post_type NOT IN ('attachment', 'revision') AND post_status NOT IN ('auto-draft', 'trash') AND post_name NOT LIKE %s AND $where_regexp;", ...$args_with_post_name_filter)); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $table_name is ok here because we exclusively loop through table names that really exist in the database. The variable $where_regexp is ok here because it is filled with escaped placeholders only a few lines before. The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            else if ($table_name === $wpdb->postmeta) { $rows_containing_img_urls = $get_results_with_timeout($wpdb->prepare("SELECT * FROM `$table_name` JOIN `$wpdb->posts` ON post_id = ID WHERE post_type NOT IN ('attachment', 'revision') AND post_status NOT IN ('auto-draft', 'trash') AND post_name NOT LIKE %s AND $where_regexp;", ...$args_with_post_name_filter)); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $table_name is ok here because we exclusively loop through table names that really exist in the database. The variable $where_regexp is ok here because it is filled with escaped placeholders only a few lines before. The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            else if ($table_name === $wpdb->options) {  $rows_containing_img_urls = $get_results_with_timeout($wpdb->prepare("SELECT * FROM `$table_name`                                     WHERE $options_without_transients_sql AND option_name NOT IN ($option_names_skipped_placeholders) AND $where_regexp;", ...$args_with_skipped_options)); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $table_name is ok here because we exclusively loop through table names that really exist in the database. The variable $where_regexp and $option_names_skipped_placeholders are ok here because they are filled with escaped placeholders only a few lines before. The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            else {                                      $rows_containing_img_urls = $get_results_with_timeout($wpdb->prepare("SELECT * FROM `$table_name`                                     WHERE $where_regexp;", ...$args)); }                                                                                                /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $table_name is ok here because we exclusively loop through table names that really exist in the database. The variable $where_regexp is ok here because it is filled with escaped placeholders only a few lines before. The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            if ($rows_containing_img_urls === false) { return false; }

            foreach ($rows_containing_img_urls as $row) {
                $row_hash = md5($column_name_maybe_with_img_url . "|" . serialize($row));
                if (isset($already_added_rows_hashes[$row_hash])) { continue; }
                $already_added_rows_hashes[$row_hash] = true;

                foreach ($img_size_urls as $relative_img_size_url => $absolute_img_url) {
                    if (strpos($row->$column_name_maybe_with_img_url, $relative_img_size_url) === false) { continue; }

                    $row_preview = [];
                    foreach ($row as $col_name_for_preview => $value) {
                        $number_of_preview_chars = 256;
                        $includes_img_url = $col_name_for_preview == $column_name_maybe_with_img_url;
                        $img_url_pos = $includes_img_url ? mb_strpos($value, $relative_img_size_url, 0, "UTF-8") : 0;
                        $start_pos = $includes_img_url ? max(0, $img_url_pos - $number_of_preview_chars / 2) : 0;
                        $end_pos = $includes_img_url ? min(mb_strlen($value, "UTF-8"), $img_url_pos + mb_strlen($relative_img_size_url, "UTF-8") + $number_of_preview_chars / 2) : $number_of_preview_chars;
                        $preview = mb_substr($value, $start_pos, $end_pos - $start_pos, "UTF-8");
                        if ($start_pos > 0) $preview = "…" . mb_substr($preview, 1, null, "UTF-8");
                        if (mb_strlen($value, "UTF-8") > $end_pos) $preview = mb_substr($preview, 0, -1, "UTF-8") . "…";
                        $preview = wp_check_invalid_utf8($preview, true);
                        $row_preview[$col_name_for_preview] = $preview;
                    }

                    $db_entries_using_images[$absolute_img_url][] = ["tableName" => $table_name, "contentPreview" => $row_preview];
                }
            }

            if (microtime(true) - $query_start_time > $max_sql_time_seconds) { return false; }
        }
    }

    $normalize_img_usage_lookup_key = function ($key) { return strtolower(\NeoAlt\NeoGlobal\preg_replace_better("/[^a-z0-9]+/i", "", (string) $key)); };
    $img_id_media_context_terms = ["image", "img", "media", "attachment", "attach", "thumbnail", "thumb", "gallery", "backgroundimage", "mediaid", "imageid", "imgid", "attachmentid", "attachid", "thumbnailid", "thumbid", "productimagegallery", "featuredimage", "featuredmedia", "postthumbnail", "poster", "logo", "avatar", "icon", "photo", "picture", "cover", "heroimage", "previewimage", "ogimage", "shareimage", "srcimage", "mobileimage", "desktopimage", "fallbackimage"];
    $img_id_layout_context_terms = ["width", "widthmax", "maxwidth", "minwidth", "height", "heightmax", "maxheight", "minheight", "fontweight", "fontstyle", "fontsize", "fontfamily", "lineheight", "letterspacing", "wordspacing", "textalign", "textdecoration", "texttransform", "padding", "paddingtop", "paddingright", "paddingbottom", "paddingleft", "margin", "margintop", "marginright", "marginbottom", "marginleft", "gap", "rowgap", "columngap", "columnsgap", "gridgap", "zindex", "opacity", "duration", "delay", "transitionduration", "animationduration", "animationdelay", "columns", "column", "size", "top", "right", "bottom", "left", "inset", "position", "offset", "translate", "translatex", "translatey", "rotate", "rotatex", "rotatey", "scale", "scalex", "scaley", "skew", "transform", "transformorigin", "blur", "brightness", "contrast", "saturate", "hue", "shadow", "boxshadow", "textshadow", "border", "borderradius", "radius", "outline", "breakpoint", "viewport", "container", "flex", "flexbasis", "flexgrow", "flexshrink", "grid", "order", "aspectratio", "objectfit", "objectposition", "color", "backgroundcolor", "gradient", "masksize", "backgroundsize", "backgroundposition", "backgroundrepeat", "display", "visibility", "overflow", "scroll", "sticky", "parallax", "speed", "easing", "threshold", "limit", "count", "index", "level", "depth", "rows", "items", "perpage", "perrow", "slides", "slidesperview", "spacing", "distance", "radiusx", "radiusy"];
    $img_usage_lookup_key_path_contains_term = function ($key_path, $terms) use ($normalize_img_usage_lookup_key) { foreach ($key_path as $key) { $normalized_key = $normalize_img_usage_lookup_key($key); foreach ($terms as $term) { if ($normalized_key !== "" && ($normalized_key === $term || str_starts_with($normalized_key, $term) || str_ends_with($normalized_key, $term))) { return true; } } } return false; };
    $img_usage_lookup_row_context_key_path = function ($row, $column_name) { $key_path = [$column_name]; if (isset($row->meta_key)) { $key_path[] = $row->meta_key; } if (isset($row->option_name)) { $key_path[] = $row->option_name; } return $key_path; };
    $img_usage_lookup_value_has_contextual_img_id = function ($value, $img_id_as_string, $row, $column_name) use ($img_id_media_context_terms, $img_id_layout_context_terms, $img_usage_lookup_key_path_contains_term, $img_usage_lookup_row_context_key_path) {
        $value_as_string = (string) $value;
        if (!\NeoAlt\NeoGlobal\preg_match_better("/(^|[^0-9])" . preg_quote($img_id_as_string, "/") . "([^0-9]|$)/", $value_as_string)) { return false; }
        $row_context_key_path = $img_usage_lookup_row_context_key_path($row, $column_name);
        $is_blocked_key_path = function ($key_path) use ($img_id_layout_context_terms, $img_usage_lookup_key_path_contains_term) { return $img_usage_lookup_key_path_contains_term($key_path, $img_id_layout_context_terms); };
        $is_media_key_path = function ($key_path) use ($img_id_media_context_terms, $img_usage_lookup_key_path_contains_term) { return $img_usage_lookup_key_path_contains_term($key_path, $img_id_media_context_terms); };
        $scalar_matches_img_id = function ($candidate) use ($img_id_as_string) { if (is_int($candidate)) { return (string) $candidate === $img_id_as_string; } if (is_string($candidate)) { return trim($candidate) === $img_id_as_string; } return false; };
        $find_contextual_img_id = function ($data, $key_path, $depth = 0) use (&$find_contextual_img_id, $scalar_matches_img_id, $is_blocked_key_path, $is_media_key_path) {
            if ($depth > 30) { return false; }
            if (is_array($data) || is_object($data)) { foreach ((is_object($data) ? get_object_vars($data) : $data) as $key => $child) { if ($find_contextual_img_id($child, array_merge($key_path, [(string) $key]), $depth + 1)) { return true; } } return false; }
            if (!$scalar_matches_img_id($data)) { return false; }
            if ($is_blocked_key_path($key_path)) { return false; }
            return $is_media_key_path($key_path);
        };

        $value_maybe_unserialized = maybe_unserialize($value_as_string);

        if ($value_maybe_unserialized !== $value_as_string && $find_contextual_img_id($value_maybe_unserialized, $row_context_key_path)) { return true; }
        $trimmed_value = trim($value_as_string);
        if ($trimmed_value !== "" && in_array($trimmed_value[0], ["{", "["], true) && ($decoded_json_value = \NeoAlt\NeoGlobal\json_decode_better($trimmed_value, suppress_error: true)) !== false && $find_contextual_img_id($decoded_json_value, $row_context_key_path)) { return true; }
        if (!$is_blocked_key_path($row_context_key_path) && $is_media_key_path($row_context_key_path) && \NeoAlt\NeoGlobal\preg_match_better("/(^|,)\\h*\"?" . preg_quote($img_id_as_string, "/") . "\"?\\h*(,|$)/", $value_as_string)) { return true; }
        $unstructured_key_value_matches = []; \NeoAlt\NeoGlobal\preg_match_all_better("/(?<key>[a-zA-Z0-9_.-]{2,})\\h*(?::|=>|=)\\h*\\\\?\"?" . preg_quote($img_id_as_string, "/") . "\\\\?\"?(?=[^0-9]|$)/i", $value_as_string, $unstructured_key_value_matches, PREG_SET_ORDER);
        foreach ($unstructured_key_value_matches as $unstructured_key_value_match) { $candidate_key_path = array_merge($row_context_key_path, [$unstructured_key_value_match["key"]]); if (!$is_blocked_key_path($candidate_key_path) && $is_media_key_path($candidate_key_path)) { return true; } }
        return false;
    };
    $img_id_as_string_by_img_url = []; $regexp_img_id_sql_parts = [];
    foreach ($img_urls as $img_url) {
        $img_id = $img_id_by_img_url[$img_url] ?? null;
        if (!$img_id) { continue; }
        $img_id_as_string = (string) $img_id; $img_id_as_string_by_img_url[$img_url] = $img_id_as_string;
        $regexp_img_id_sql_parts[] = $img_id_as_string;
    }
    $regexp_img_id_sql_chunks = []; $current = "";
    foreach ($regexp_img_id_sql_parts as $part) {
        if ($current !== "" && strlen($current) + strlen($part) + 1 > $max_regex_chunk_len) { $regexp_img_id_sql_chunks[] = $current; $current = $part; } else { $current .= ($current === "" ? "" : "|") . $part; }
    }
    if ($current !== "") { $regexp_img_id_sql_chunks[] = $current; }
    foreach ($text_columns_by_table_name as $table_name => $column_names_maybe_with_img_id) {
        foreach ($column_names_maybe_with_img_id as $column_name_maybe_with_img_id) {
            if (empty($regexp_img_id_sql_chunks)) { continue; }
            $where_parts = []; $args = [];
            foreach ($regexp_img_id_sql_chunks as $chunk) { $where_parts[] = "`$column_name_maybe_with_img_id` REGEXP %s"; $args[] = $chunk; }
            $where_regexp = "(" . implode(" OR ", $where_parts) . ")";
            $args_with_skipped_options = array_merge($option_names_skipped_for_usage_lookup, $args);
                 if ($table_name === $wpdb->posts) {    $rows_containing_img_ids = $get_results_with_timeout($wpdb->prepare("SELECT * FROM `$table_name`                                     WHERE post_type NOT IN ('attachment', 'revision') AND post_status NOT IN ('auto-draft', 'trash') AND post_name NOT LIKE %s AND $where_regexp;", ...array_merge(["%-autosave-v%"], $args))); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $table_name is ok here because we exclusively loop through table names that really exist in the database. The variable $where_regexp is ok here because it is filled with escaped placeholders only a few lines before. The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            else if ($table_name === $wpdb->postmeta) { $rows_containing_img_ids = $get_results_with_timeout($wpdb->prepare("SELECT `$table_name`.* FROM `$table_name` JOIN `$wpdb->posts` ON post_id = ID WHERE post_type NOT IN ('attachment', 'revision') AND post_status NOT IN ('auto-draft', 'trash') AND post_name NOT LIKE %s AND $where_regexp;", ...array_merge(["%-autosave-v%"], $args))); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $table_name is ok here because we exclusively loop through table names that really exist in the database. The variable $where_regexp is ok here because it is filled with escaped placeholders only a few lines before. The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            else if ($table_name === $wpdb->options) {  $rows_containing_img_ids = $get_results_with_timeout($wpdb->prepare("SELECT * FROM `$table_name`                                     WHERE $options_without_transients_sql AND option_name NOT IN ($option_names_skipped_placeholders) AND $where_regexp;", ...$args_with_skipped_options)); } /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $table_name is ok here because we exclusively loop through table names that really exist in the database. The variable $where_regexp and $option_names_skipped_placeholders are ok here because they are filled with escaped placeholders only a few lines before. The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            else {                                      $rows_containing_img_ids = $get_results_with_timeout($wpdb->prepare("SELECT * FROM `$table_name`                                     WHERE $where_regexp;", ...$args)); }                                                                                                /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The variable $table_name is ok here because we exclusively loop through table names that really exist in the database. The variable $where_regexp is ok here because it is filled with escaped placeholders only a few lines before. The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            if ($rows_containing_img_ids === false) { return false; }
            foreach ($rows_containing_img_ids as $row) {
                foreach ($img_id_as_string_by_img_url as $img_url => $img_id_as_string) {
                    if (!$img_usage_lookup_value_has_contextual_img_id($row->$column_name_maybe_with_img_id, $img_id_as_string, $row, $column_name_maybe_with_img_id)) { continue; }
                    $row_preview = [];
                    foreach ($row as $col_name_for_preview => $value) {
                        $number_of_preview_chars = 256; $includes_img_id = $col_name_for_preview == $column_name_maybe_with_img_id;
                        $exact_img_id_preview_matches = []; $img_id_pos = $includes_img_id && \NeoAlt\NeoGlobal\preg_match_better("/(^|[^0-9])" . preg_quote($img_id_as_string, "/") . "([^0-9]|$)/", (string) $value, $exact_img_id_preview_matches, PREG_OFFSET_CAPTURE) ? $exact_img_id_preview_matches[0][1] + strlen($exact_img_id_preview_matches[1][0]) : 0;
                        $start_pos = $includes_img_id ? max(0, $img_id_pos - $number_of_preview_chars / 2) : 0;
                        $end_pos = $includes_img_id ? min(mb_strlen((string) $value, "UTF-8"), $img_id_pos + strlen($img_id_as_string) + $number_of_preview_chars / 2) : $number_of_preview_chars;
                        $preview = mb_substr((string) $value, $start_pos, $end_pos - $start_pos, "UTF-8");
                        if ($start_pos > 0) { $preview = "…" . mb_substr($preview, 1, null, "UTF-8"); }
                        if (mb_strlen((string) $value, "UTF-8") > $end_pos) { $preview = mb_substr($preview, 0, -1, "UTF-8") . "…"; }
                        $preview = wp_check_invalid_utf8($preview, true);
                        $row_preview[$col_name_for_preview] = $preview;
                    }
                    $db_entries_using_images[$img_url][] = ["tableName" => $table_name, "contentPreview" => $row_preview];
                }
                if (microtime(true) - $query_start_time > $max_sql_time_seconds) { return false; }
            }
        }
    }

    foreach ($db_entries_using_images as $img_url => &$db_entries_using_img) {
        $already_seen_db_entry_keys = [];
        $db_entries_using_img = \NeoAlt\NeoGlobal\array_filter_better($db_entries_using_img, function ($db_entry) use (&$already_seen_db_entry_keys, $wpdb) {
            $content_preview = $db_entry["contentPreview"];
                 if ($db_entry["tableName"] === $wpdb->postmeta && isset($content_preview["post_id"], $content_preview["meta_key"], $content_preview["meta_value"])) { $db_entry_key = $db_entry["tableName"] . "|" . $content_preview["post_id"] . "|" . $content_preview["meta_key"] . "|" . $content_preview["meta_value"]; }
            else if (($db_entry["tableName"] === "wp_options" || $db_entry["tableName"] === $wpdb->options) && isset($content_preview["option_name"], $content_preview["option_value"])) { $db_entry_key = $db_entry["tableName"] . "|" . $content_preview["option_name"] . "|" . $content_preview["option_value"]; }
            else if ($db_entry["tableName"] === $wpdb->posts && isset($content_preview["ID"])) { $db_entry_key = $db_entry["tableName"] . "|" . $content_preview["ID"]; }
            else { $db_entry_key = $db_entry["tableName"] . "|" . \NeoAlt\NeoGlobal\json_encode_better($content_preview); }
            if (isset($already_seen_db_entry_keys[$db_entry_key])) { return false; }
            $already_seen_db_entry_keys[$db_entry_key] = true; return true;
        });
    }

    foreach ($db_entries_using_images as $img_url => &$db_entries_using_img) {
        foreach ($db_entries_using_img as &$db_entry) {
            $row_preview_post = false;
            if ($db_entry["tableName"] === $wpdb->posts) {
                $row_preview_post = get_post($db_entry["contentPreview"]["ID"]);
            } else if ($db_entry["tableName"] === $wpdb->postmeta) {
                $row_preview_post = get_post($db_entry["contentPreview"]["post_id"]);
            }

            if ($row_preview_post instanceof \WP_Post) {
                $db_entry["postUrl"] = get_permalink($row_preview_post);
                $db_entry["postId"] = $row_preview_post->ID;
                $db_entry["postTitle"] = $row_preview_post->post_title;
                $db_entry["postUrlDisplayPath"] = \NeoAlt\NeoGlobal\make_internal_url_relative($db_entry["postUrl"]);
                $db_entry["postUrlDisplayPath"] = \NeoAlt\NeoGlobal\preg_replace_better("#^/scope:[^/]+(?=/)#", "", $db_entry["postUrlDisplayPath"]);
                $db_entry["postUrlDisplayPath"] = str_starts_with($db_entry["postUrlDisplayPath"], "/?p=") ? substr($db_entry["postUrlDisplayPath"], 1)     : $db_entry["postUrlDisplayPath"];
                $db_entry["postUrlDisplayPath"] = str_ends_with(  $db_entry["postUrlDisplayPath"], "/")    ? substr($db_entry["postUrlDisplayPath"], 0, -1) : $db_entry["postUrlDisplayPath"];
                $db_entry["postUrlDisplayPath"] = $db_entry["postUrlDisplayPath"] === "" ? "/" : $db_entry["postUrlDisplayPath"];
            } else {
                $db_entry["postUrl"]            = null;
                $db_entry["postUrlDisplayPath"] = null;
                $db_entry["postId"]             = null;
                $db_entry["postTitle"]          = null;
            }
        }
    }

    foreach ($db_entries_using_images as $img_url => &$db_entries_using_img) {
        $db_entries_using_img = \NeoAlt\NeoGlobal\array_filter_better($db_entries_using_img, function ($db_entry) use ($option_names_skipped_for_usage_lookup) {
            return !($db_entry["tableName"] === "wp_options" && (str_starts_with($db_entry["contentPreview"]["option_name"] ?? "", "_transient_") || str_starts_with($db_entry["contentPreview"]["option_name"] ?? "", "_site_transient_") || in_array($db_entry["contentPreview"]["option_name"] ?? "", $option_names_skipped_for_usage_lookup)));
        });
    }

    if (!$include_content_preview) { foreach ($db_entries_using_images as &$db_entries_using_img) { foreach ($db_entries_using_img as &$db_entry) { unset($db_entry["contentPreview"]); } } }

    return $db_entries_using_images;
}

\NeoAlt\NeoGlobal\register_rest_endpoint("/wp-json/neo/db-entries-usage-neo-alt", "GET", fn () => \NeoAlt\NeoGlobal\current_user_can__global_db_entries_usage(), function ($get_param) {
    $img_url = \NeoAlt\NeoGlobal\percent_decode_invalid_utf8_url_bytes(\NeoAlt\NeoGlobal\remove_all_query_params((string) $get_param("img-url")));
    if (!$img_url) { \NeoAlt\NeoGlobal\throw_global_exception("Missing image URL.", status_code: 400); }
    $timeout_seconds = max(0, (int) ($get_param("timeout") ?: 10));

    $usage_lookup = db_entries_image_usage_lookup([$img_url], max_sql_time_seconds: $timeout_seconds, include_content_preview: \NeoAlt\NeoGlobal\current_user_can__global_db_entries_details());
    return ["dbEntriesUsingImage" => $usage_lookup === false ? false : ($usage_lookup[$img_url] ?? []), "timeoutSeconds" => $timeout_seconds, "imgUrl" => \NeoAlt\NeoGlobal\percent_encode_invalid_utf8_url_bytes($img_url)];
});

\NeoAlt\NeoGlobal\add_action_hook("neo_init", function () {
    $db_entries_usage_url = \NeoAlt\NeoGlobal\get_backend_page_url("neo-global--db-entries-usage-neo-alt");
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoGlobalDbEntriesUsageUrl", $db_entries_usage_url);
});
