<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Rename engine for reliably replacing all image references in the database using sanitized and escaped SQL commands, ensuring that even standalone image references in the database are replaced reliably.
function replace_media_urls_in_db($urls_to_replace, $skip_thread_stack_retry = false) {
    global $wpdb;
    $urls_to_replace = \NeoRename\NeoGlobal\array_filter_better($urls_to_replace, fn ($url_to_replace) => ($url_to_replace["old_url"] ?? "") !== "" && ($url_to_replace["new_url"] ?? "") !== "" && $url_to_replace["old_url"] !== $url_to_replace["new_url"]);
    if (count($urls_to_replace) === 0) { return null; }
    usort($urls_to_replace, function ($a, $b) { return strlen($b["old_url"]) <=> strlen($a["old_url"]); });

    $thread_stack_savepoint_name = "neo_global__replace_media_urls_thread_stack";
    $thread_stack_savepoint_created = !$skip_thread_stack_retry && count($urls_to_replace) > 1 && $wpdb->query("SAVEPOINT $thread_stack_savepoint_name") !== false;
    $sql_scripts = [];

    $replacements1 = [];
    $replacements2 = [];
    $replacements3 = [];
    foreach ($urls_to_replace as $url_to_replace) {
        $old_image_url_absolute = $url_to_replace["old_url"];
        $new_image_url_absolute = $url_to_replace["new_url"];
        $old_image_url_absolute_http = str_replace("https://", "http://", $old_image_url_absolute);
        $new_image_url_absolute_http = str_replace("https://", "http://", $new_image_url_absolute);
        $old_image_url_absolute_https = str_replace("http://", "https://", $old_image_url_absolute);
        $new_image_url_absolute_https = str_replace("http://", "https://", $new_image_url_absolute);
        $old_image_url_relative = \NeoRename\NeoGlobal\make_internal_url_relative($old_image_url_absolute);
        $new_image_url_relative = \NeoRename\NeoGlobal\make_internal_url_relative($new_image_url_absolute);
        $old_image_url_relative_no_first_slash = ltrim($old_image_url_relative, "/");
        $new_image_url_relative_no_first_slash = ltrim($new_image_url_relative, "/");
        $old_path_rel_with_dir = \NeoRename\NeoGlobal\make_internal_url_relative_to_uploads($old_image_url_absolute);
        $new_path_rel_with_dir = \NeoRename\NeoGlobal\make_internal_url_relative_to_uploads($new_image_url_absolute);

        $replacements1[] = ["old_str" => $old_path_rel_with_dir,             "new_str" => $new_path_rel_with_dir            ];
        $replacements1[] = ["old_str" => '"' . $old_path_rel_with_dir . '"', "new_str" => '"' . $new_path_rel_with_dir . '"'];
        $replacements1[] = ["old_str" => "'" . $old_path_rel_with_dir . "'", "new_str" => "'" . $new_path_rel_with_dir . "'"];

        $replacements2[]= ["old_str" => 's:' . strlen($old_image_url_absolute_https)          . ':"' . $old_image_url_absolute_https          . '"', "new_str" => 's:' . strlen($new_image_url_absolute_https)          . ':"' . $new_image_url_absolute_https          . '"' ];
        $replacements2[]= ["old_str" => 's:' . strlen($old_image_url_absolute_http)           . ':"' . $old_image_url_absolute_http           . '"', "new_str" => 's:' . strlen($new_image_url_absolute_http)           . ':"' . $new_image_url_absolute_http           . '"' ];
        $replacements2[]= ["old_str" => 's:' . strlen($old_image_url_relative)                . ':"' . $old_image_url_relative                . '"', "new_str" => 's:' . strlen($new_image_url_relative)                . ':"' . $new_image_url_relative                . '"' ];
        $replacements2[]= ["old_str" => 's:' . strlen($old_image_url_relative_no_first_slash) . ':"' . $old_image_url_relative_no_first_slash . '"', "new_str" => 's:' . strlen($new_image_url_relative_no_first_slash) . ':"' . $new_image_url_relative_no_first_slash . '"' ];
        $replacements2[]= ["old_str" => 's:' . strlen($old_path_rel_with_dir)                 . ':"' . $old_path_rel_with_dir                 . '"', "new_str" => 's:' . strlen($new_path_rel_with_dir)                 . ':"' . $new_path_rel_with_dir                 . '"' ];
        $lower_percent_encoding = function ($url_encoded_string) { return \NeoRename\NeoGlobal\preg_replace_callback_better("/%[0-9A-F]{2}/", fn ($match) => strtolower($match[0]), $url_encoded_string); };
        $url_encoded_replacements = [
            ["old_str" => rawurlencode($old_image_url_absolute_https),                                          "new_str" => rawurlencode($new_image_url_absolute_https)],
            ["old_str" => rawurlencode($old_image_url_absolute_http),                                           "new_str" => rawurlencode($new_image_url_absolute_http)],
            ["old_str" => rawurlencode($old_image_url_relative),                                                "new_str" => rawurlencode($new_image_url_relative)],
            ["old_str" => rawurlencode($old_image_url_relative_no_first_slash),                                 "new_str" => rawurlencode($new_image_url_relative_no_first_slash)],
            ["old_str" => str_replace(["%3A", "%2F"], [":", "/"], rawurlencode($old_image_url_absolute_https)), "new_str" => str_replace(["%3A", "%2F"], [":", "/"], rawurlencode($new_image_url_absolute_https))],
            ["old_str" => str_replace(["%3A", "%2F"], [":", "/"], rawurlencode($old_image_url_absolute_http)),  "new_str" => str_replace(["%3A", "%2F"], [":", "/"], rawurlencode($new_image_url_absolute_http))],
            ["old_str" => str_replace("%2F", "/", rawurlencode($old_image_url_relative)),                       "new_str" => str_replace("%2F", "/", rawurlencode($new_image_url_relative))],
            ["old_str" => str_replace("%2F", "/", rawurlencode($old_image_url_relative_no_first_slash)),        "new_str" => str_replace("%2F", "/", rawurlencode($new_image_url_relative_no_first_slash))],
        ];
        foreach ($url_encoded_replacements as $url_encoded_replacement) {
            foreach ([$url_encoded_replacement, ["old_str" => $lower_percent_encoding($url_encoded_replacement["old_str"]), "new_str" => $lower_percent_encoding($url_encoded_replacement["new_str"])]] as $url_encoded_replacement_variant) {
                $replacements2[]= ["old_str" => 's:' . strlen($url_encoded_replacement_variant["old_str"]) . ':"' . $url_encoded_replacement_variant["old_str"] . '"', "new_str" => 's:' . strlen($url_encoded_replacement_variant["new_str"]) . ':"' . $url_encoded_replacement_variant["new_str"] . '"' ];
                $replacements3[]= ["old_str" => $url_encoded_replacement_variant["old_str"], "new_str" => $url_encoded_replacement_variant["new_str"]];
            }
        }

        $replacements3[]= ["old_str" => $old_image_url_relative_no_first_slash                                                                     , "new_str" => $new_image_url_relative_no_first_slash,                                                                     ];
        $replacements3[]= ["old_str" => substr(json_encode(["x" => $old_image_url_relative_no_first_slash]),                         6, -2)        , "new_str" => substr(json_encode(["x" => $new_image_url_relative_no_first_slash]),                         6, -2)         ];
        $replacements3[]= ["old_str" => substr(json_encode(["x" => $old_image_url_relative_no_first_slash], JSON_UNESCAPED_UNICODE), 6, -2)        , "new_str" => substr(json_encode(["x" => $new_image_url_relative_no_first_slash], JSON_UNESCAPED_UNICODE), 6, -2)         ];
    }

    $replacements1 = \NeoRename\NeoGlobal\array_unique_better($replacements1);
    $replacements2 = \NeoRename\NeoGlobal\array_unique_better($replacements2);
    $replacements3 = \NeoRename\NeoGlobal\array_unique_better($replacements3);

    $replacing_file_extensions = \NeoRename\NeoGlobal\array_filter_better(\NeoRename\NeoGlobal\array_unique_better(array_map(function ($url) { return strtolower(pathinfo(wp_parse_url($url["old_url"], PHP_URL_PATH) ?: $url["old_url"], PATHINFO_EXTENSION)); }, $urls_to_replace)));
    if (count($replacing_file_extensions) === 0) { return null; }
    $regexp_replacing_file_extensions_filter = "(?i)\\.(" . implode("|", array_map("preg_quote", $replacing_file_extensions)) . ")";

    $get_revision_trash_filter_sql = function ($table_name) use ($wpdb) {
        if ($table_name === $wpdb->posts)    { return $wpdb->prepare(" AND `post_type` != %s AND `post_status` != %s", "revision", "trash"); }
        if ($table_name === $wpdb->postmeta) { return $wpdb->prepare(" AND NOT EXISTS (SELECT 1 FROM %i AS `revision_trash_posts_to_skip` WHERE `revision_trash_posts_to_skip`.`ID` = %i.`post_id` AND (`revision_trash_posts_to_skip`.`post_type` = %s OR `revision_trash_posts_to_skip`.`post_status` = %s))", $wpdb->posts, $table_name, "revision", "trash"); }
        return "";
    };
    $table_columns_including_images = [];
    foreach ($wpdb->get_results("SHOW TABLES", ARRAY_N) as $table) { /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
        $table_name = $table[0];
        if (strpos($table_name, $wpdb->prefix) !== 0) { continue; }
        foreach ($wpdb->get_results("SHOW COLUMNS FROM `$table_name`") as $column) { /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. */
            if (!(strtolower($column->Type) === "json" || strpos($column->Type, "char") !== false || strpos($column->Type, "text") !== false)) { continue; }

            $column_name = $column->Field;

            $table_image_lookup_sql = $wpdb->prepare("SELECT * FROM %i WHERE %i REGEXP %s", $table_name, $column_name, $regexp_replacing_file_extensions_filter) . $get_revision_trash_filter_sql($table_name) . " LIMIT 1;";
            if (count($wpdb->get_results($table_image_lookup_sql)) > 0) { /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. The variable $table_image_lookup_sql is prepared safely above. */
                if (!isset($table_columns_including_images[$table_name])) { $table_columns_including_images[$table_name] = []; }
                $table_columns_including_images[$table_name][] = $column_name;
            }
        }
    }

    $old_replacement_strings_for_lcs = \NeoRename\NeoGlobal\array_unique_better(\NeoRename\NeoGlobal\array_filter_better(array_map(fn ($replacement) => $replacement["old_str"] ?? "", array_merge($replacements1, $replacements2, $replacements3))));
    usort($old_replacement_strings_for_lcs, fn ($a, $b) => strlen($a) <=> strlen($b));

    foreach ($table_columns_including_images as $table_name => $columns) {
        $potential_filter_lcs = "";
        $potential_filter_base = $old_replacement_strings_for_lcs[0] ?? "";
        for ($substring_length = strlen($potential_filter_base); $substring_length >= 2 && $potential_filter_lcs === ""; $substring_length--) {
            for ($substring_start = 0; $substring_start <= strlen($potential_filter_base) - $substring_length; $substring_start++) {
                $potential_filter_candidate = substr($potential_filter_base, $substring_start, $substring_length);
                $potential_filter_candidate_matches = true;
                foreach ($old_replacement_strings_for_lcs as $old_replacement_string_for_lcs) { if (stripos($old_replacement_string_for_lcs, $potential_filter_candidate) === false) { $potential_filter_candidate_matches = false; break; } }
                if (!$potential_filter_candidate_matches) { continue; }
                $potential_filter_lcs = $potential_filter_candidate; break;
            }
        }
        $regexp_replacing_potential_filter = null;
        if (strlen($potential_filter_lcs) >= 2) { $regexp_replacing_potential_filter = esc_sql("(?i)" . preg_quote($potential_filter_lcs)); }

        $sql_cmd = "UPDATE `" . $table_name . "` SET ";
        foreach ($columns as $column_name) {
            $sql_cmd .= "`" . $column_name . "` = CASE ";
            $sql_cmd .= "WHEN BINARY `" . $column_name . "` IN (";
            $sql_cmd .= implode(", ", array_map(fn ($p) => "'" . esc_sql($p["old_str"]) . "'", $replacements1)) . ") THEN ";
            $sql_cmd .= str_repeat("REPLACE(", count($replacements1));
            $sql_cmd .= "`" . $column_name . "`";
            foreach ($replacements1 as $p) { $sql_cmd .= ", '" . esc_sql($p["old_str"]) . "', '" . esc_sql($p["new_str"]) . "')"; }
            $sql_cmd .= " WHEN LEFT(`" . $column_name . "`, 2) = 'C:' THEN `" . $column_name . "` ";
            $sql_cmd .= " WHEN LEFT(`" . $column_name . "`, 2) IN ('a:', 'O:', 's:') THEN ";
            $sql_cmd .= str_repeat("REPLACE(", count($replacements2));
            $sql_cmd .= "`" . $column_name . "`";
            foreach ($replacements2 as $replacement) { $sql_cmd .= ", '" . esc_sql($replacement["old_str"]) . "', '" . esc_sql($replacement["new_str"]) . "')"; }
            $sql_cmd .= " WHEN LEFT(`" . $column_name . "`, 2) NOT IN ('a:', 'O:', 's:') THEN ";
            $sql_cmd .= str_repeat("REPLACE(", count($replacements3));
            $sql_cmd .= "`" . $column_name . "`";
            foreach ($replacements3 as $replacement) { $sql_cmd .= ", '" . esc_sql($replacement["old_str"]) . "', '" . esc_sql($replacement["new_str"]) . "')"; }
            $sql_cmd .= " ELSE `" . $column_name . "` ";
            $sql_cmd .= "END, ";
        }
        $sql_cmd = rtrim($sql_cmd, ", ");
        $where_sql = "";
        foreach ($columns as $column_name) {
            $where_sql .= $wpdb->prepare("(%i REGEXP %s", $column_name, $regexp_replacing_file_extensions_filter) . ($regexp_replacing_potential_filter === null ? "" : " AND `$column_name` REGEXP '$regexp_replacing_potential_filter'") . ") OR ";
        }
        $where_sql = str_ends_with($where_sql, " OR ") ? substr($where_sql, 0, -4) : $where_sql;
        $where_sql = "(" . $where_sql . ")" . $get_revision_trash_filter_sql($table_name);
        $sql_cmd .= " WHERE " . $where_sql;
        $sql_cmd .= ";";
        $lock_candidates_sql_cmd = "SELECT 1 FROM `" . $table_name . "` WHERE " . $where_sql . " FOR UPDATE;";
        $sql_scripts[] = ["sql_cmd" => $sql_cmd, "lock_candidates_sql_cmd" => $lock_candidates_sql_cmd];
    }
    $error_string = null;
    $db_error_code = 0;

    foreach ($sql_scripts as $sql_script) {
        for ($lock_attempt = 1; $lock_attempt <= 10; $lock_attempt++) {
            $lock_success = $wpdb->query($sql_script["lock_candidates_sql_cmd"]); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. The variable $lock_candidates_sql_cmd is prepared safely above. */
            if ($lock_success !== false) { break; }
            $db_error_code = is_object($wpdb->dbh) && isset($wpdb->dbh->errno) ? (int) $wpdb->dbh->errno : 0;
            if ($db_error_code === 1020 && $lock_attempt < 10) { usleep(100000); continue; }
            $error_string = \NeoRename\NeoGlobal\neo__("Error while locking database rows. Database error: ", "Fehler beim Sperren der Datenbank-Zeilen. Datenbankfehler: ") . ($wpdb->last_error ?: "-") . \NeoRename\NeoGlobal\neo__(" SQL command: ", " SQL Befehl: ") . $sql_script["lock_candidates_sql_cmd"]; break;
        }
        if ($error_string !== null) { break; }
    }
    foreach ($sql_scripts as $sql_script) {
        if ($error_string !== null) { break; }
        for ($update_attempt = 1; $update_attempt <= 10; $update_attempt++) {
            $database_errors_suppressed = $wpdb->suppress_errors(true); $success = $wpdb->query($sql_script["sql_cmd"]); $wpdb->suppress_errors($database_errors_suppressed); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter */ /* The direct database access is unavoidable because WP doesn't provide a helper function to perform this operation. The variable $sql_cmd is prepared safely above. */
            if ($success !== false) { break; }
            $db_error_code = is_object($wpdb->dbh) && isset($wpdb->dbh->errno) ? intval($wpdb->dbh->errno) : 0;
            if ($db_error_code === 1020 && $update_attempt < 10) { usleep(100000); continue; }
            $error_string = \NeoRename\NeoGlobal\neo__("Error while renaming URLs in database. Database error: ", "Fehler beim Umbenennen von URLs in der Datenbank. Datenbankfehler: ") . ($wpdb->last_error ?: "-") . \NeoRename\NeoGlobal\neo__(" SQL command: ", " SQL Befehl: ") . $sql_script["sql_cmd"]; break;
        }
    }

    if ($error_string !== null && $db_error_code === 1436 && $thread_stack_savepoint_created) {
        $original_error_string = $error_string;
        for ($thread_stack_batch_count = 2; $thread_stack_batch_count <= count($urls_to_replace); $thread_stack_batch_count = min(count($urls_to_replace), $thread_stack_batch_count * 2)) {
            if ($wpdb->query("ROLLBACK TO SAVEPOINT $thread_stack_savepoint_name") === false) { return $original_error_string . \NeoRename\NeoGlobal\neo__(" Retrying failed because the database savepoint could not be restored: ", " Erneuter Versuch fehlgeschlagen, weil der Datenbank-Savepoint nicht wiederhergestellt werden konnte: ") . ($wpdb->last_error ?: "-"); }
            $error_string = null;
            \NeoRename\NeoGlobal\global_log_with_module_name("_global-rename-image-engine", "Retry media URL replacements after database thread stack overrun: " . \NeoRename\NeoGlobal\json_encode_better(["urlCount" => count($urls_to_replace), "batchCount" => $thread_stack_batch_count]));
            foreach (array_chunk($urls_to_replace, intval(ceil(count($urls_to_replace) / $thread_stack_batch_count))) as $urls_to_replace_batch) {
                $error_string = replace_media_urls_in_db($urls_to_replace_batch, true);
                if ($error_string !== null) { $db_error_code = is_object($wpdb->dbh) && isset($wpdb->dbh->errno) ? intval($wpdb->dbh->errno) : 0; break; }
            }
            if ($error_string === null) { return null; }
            if ($db_error_code !== 1436 || $thread_stack_batch_count === count($urls_to_replace)) { return $error_string; }
        }
    }
    return $error_string;
}
