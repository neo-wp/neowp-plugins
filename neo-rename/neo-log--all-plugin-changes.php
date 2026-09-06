<?php
namespace NeoRename\NeoLog; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\add_action_hook("current_screen", function () {
    $log_data = ["siteHost" => \NeoRename\NeoGlobal\site_host(), "activePlugins" => \NeoRename\NeoEntrypoint\get_neo_active_plugins(), "wpVersion" => get_bloginfo("version"), "phpVersion" => phpversion(), "plugins" => get_option("active_plugins"), "wpContentPath" => WP_CONTENT_DIR]; /* Usage of WP_CONTENT_DIR is OK here because it is only meant for debugging when the user provides us their log files #suppressLinterWporgDirectoryConstantCheck */

    $global_log_path = \NeoRename\NeoGlobal\cache_path("neo-log") . "/neo-log--plugin-changes.log";
    $get_last_non_empty_line = function ($file_path) { return !\NeoRename\NeoGlobal\fs_file_exists($file_path) ? null : ((($file = new \SplFileObject($file_path, "r")) && $file->seek(PHP_INT_MAX) === null && ($line = trim($file->current())) !== "") ? $line : ((($file->key() > 0 && $file->seek($file->key() - 1) === null && ($line = trim($file->current())) !== "")) ? $line : (($file->seek(0) === null && ($line = trim($file->current())) !== "") ? $line : null))); };
    $last_global_log_line = \NeoRename\NeoGlobal\json_decode_better($get_last_non_empty_line($global_log_path) ?? "{}", suppress_error: true); $last_global_log_message = $last_global_log_line === false ? null : ($last_global_log_line["message"] ?? null);
    $last_global_log_data = is_string($last_global_log_message) ? \NeoRename\NeoGlobal\json_decode_better($last_global_log_message, suppress_error: true) : false;

    $log_data_for_comparison = $log_data; unset($log_data_for_comparison["databaseTables"], $log_data_for_comparison["databaseTablesError"]);
    if (is_array($last_global_log_data)) { unset($last_global_log_data["databaseTables"], $last_global_log_data["databaseTablesError"]); }
    if ($last_global_log_data == $log_data_for_comparison) { return; }
    global $wpdb;
    $database_tables = $wpdb->get_results($wpdb->prepare("SELECT TABLE_NAME AS tableName, ENGINE AS engine, TABLE_ROWS AS estimatedRows, DATA_LENGTH AS dataSizeBytes, INDEX_LENGTH AS indexSizeBytes, DATA_LENGTH + INDEX_LENGTH AS totalSizeBytes FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s ORDER BY totalSizeBytes DESC, TABLE_NAME ASC", $wpdb->dbname), ARRAY_A); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* information_schema is required to read all table sizes without expensive COUNT queries. */
    if ($database_tables === null) { $log_data["databaseTablesError"] = $wpdb->last_error; } else { $log_data["databaseTables"] = array_map(function ($table) { return ["tableName" => $table["tableName"], "engine" => $table["engine"], "estimatedRows" => $table["estimatedRows"] === null ? null : (int) $table["estimatedRows"], "dataSizeBytes" => (int) $table["dataSizeBytes"], "indexSizeBytes" => (int) $table["indexSizeBytes"], "totalSizeBytes" => (int) $table["totalSizeBytes"]]; }, $database_tables); }

    $log_message = \NeoRename\NeoGlobal\json_encode_better($log_data);
    \NeoRename\NeoGlobal\global_log_with_module_name("neo-log", $log_message, log_source: "neo-log--plugin-changes");
});
