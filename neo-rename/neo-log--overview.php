<?php
namespace NeoRename\NeoLog; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\register_backend_page("neo-log", fn () => \NeoRename\NeoGlobal\current_user_can__neo_log__view(), function () {
    $log_dir = \NeoRename\NeoGlobal\cache_path("neo-log");
    $warnings = [];
    $file_infos = [];
    $all_logs = [];
    $per_file_limit = (int) (\NeoRename\NeoGlobal\query_param("limit") ?? 100);

    if (\NeoRename\NeoGlobal\query_param("download-all-logs") === "1") {
        $cache_dir_path = WP_CONTENT_DIR . "/cache"; /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
        $zip_dir_path = \NeoRename\NeoGlobal\cache_path("neo-log__download");
        $zip_file_path = $zip_dir_path . "/neo-log--all-plugins-" . \NeoRename\NeoGlobal\unique_planck_date() . ".zip";
        $download_filename = (wp_parse_url(site_url(), PHP_URL_HOST) ?: "unknown") . "_" . \NeoRename\NeoGlobal\wp_date_string("Y-m-d H-i-s") . "_neo-log.zip";
        $log_dir_paths = array_merge(\NeoRename\NeoGlobal\fs_glob($cache_dir_path . "/*/neo-log", GLOB_ONLYDIR) ?: [], \NeoRename\NeoGlobal\fs_glob($cache_dir_path . "/*/neo-log--*", GLOB_ONLYDIR) ?: []);
        $log_dir_paths = \NeoRename\NeoGlobal\array_unique_better($log_dir_paths); sort($log_dir_paths, SORT_STRING);
        try {
            $zip = new \ZipArchive();
            if ($zip->open($zip_file_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) { \NeoRename\NeoGlobal\throw_global_exception("Could not create neoLog ZIP."); }
            $added_files_count = 0;
            foreach ($log_dir_paths as $log_dir_path) {
                if (!str_starts_with($log_dir_path, $cache_dir_path . "/")) { continue; }
                $plugin_cache_folder = basename(dirname($log_dir_path));
                $log_folder = basename($log_dir_path);
                foreach (\NeoRename\NeoGlobal\iterate_all_files($log_dir_path) as $log_file_path) {
                    if (!\NeoRename\NeoGlobal\fs_is_file($log_file_path)) { continue; }
                    $zip_entry_path = $plugin_cache_folder . "/" . $log_folder . "/" . ltrim(substr($log_file_path, strlen($log_dir_path)), "/");
                    if ($zip->addFile($log_file_path, $zip_entry_path)) { $added_files_count++; }
                }
            }
            if ($added_files_count === 0) { $zip->addFromString("README.txt", "No neoLog files found in " . $cache_dir_path . ".\n"); }
            $zip->close();
            header("Content-Description: File Transfer");
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=\"" . basename($download_filename) . "\"");
            header("Expires: 0");
            header("Cache-Control: must-revalidate");
            header("Content-Length: " . \NeoRename\NeoGlobal\fs_filesize($zip_file_path));
            \NeoRename\NeoGlobal\fs_readfile($zip_file_path);
        } finally {
            if (\NeoRename\NeoGlobal\fs_file_exists($zip_file_path)) { \NeoRename\NeoGlobal\fs_unlink($zip_file_path); }
        }
        exit;
    }

    if (!\NeoRename\NeoGlobal\fs_is_dir($log_dir)) { echo "Warning: The log directory does not exist: " . esc_html($log_dir); exit; }
    $log_files = \NeoRename\NeoGlobal\iterate_all_files($log_dir);

    foreach ($log_files as $full_path) {
        if (basename($full_path) === "_all.log") { continue; }

        $file_size_bytes = \NeoRename\NeoGlobal\fs_filesize($full_path);
        if ($file_size_bytes === false) { $file_size_bytes = 0; $warnings[] = "Warning: Could not get file size for {$full_path}"; }
        $file_size_kb = round($file_size_bytes / 1024);

        $file_logs = [];
        $file_obj = new \SplFileObject($full_path, "r");
        if (!$file_obj->isReadable()) { $warnings[] = "Warning: Could not open file {$full_path} for reading"; $file_infos[] = ["file" => basename($full_path), "count" => 0, "size" => $file_size_kb]; continue; }
        $file_obj->seek(PHP_INT_MAX);
        $last_line_num = $file_obj->key();
        $line_counter = 0;
        for ($i = $last_line_num; $i >= 0 && $line_counter < $per_file_limit; $i--) {
            $file_obj->seek($i);
            $line = trim($file_obj->current()); if ($line === "") { continue; }
            $decoded = \NeoRename\NeoGlobal\json_decode_better($line, suppress_error: true); if ($decoded === false) { $warnings[] = "Warning: Could not decode JSON line in file $full_path: $line"; continue; }
            $decoded["_raw"] = $line;
            if (!isset($decoded["date"])) { $warnings[] = "Warning: Missing 'date' in file {$full_path}: {$line}"; continue; }
            if (!isset($decoded["message"])) { $json_string = \NeoRename\NeoGlobal\json_encode_better($decoded); if (mb_strlen($json_string) > 1000) { $json_string = mb_substr($json_string, 0, 1000) . "..."; } $decoded["message"] = $json_string; }
            $file_logs[] = ["date" => $decoded["date"], "message" => $decoded["message"], "raw" => $decoded["_raw"]]; $line_counter++;
        }
        unset($file_obj);

        $tokenize_message = function ($message) {
            $tokens = \NeoRename\NeoGlobal\preg_split_better('/(\s+|[{}\[\],:])/', $message, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            return ($tokens === false || empty($tokens)) ? [$message] : $tokens;
        };

        $render_tokens = function ($tokens) { return implode("", $tokens); };

        $common_prefix = function ($list_of_token_arrays) {
            if (empty($list_of_token_arrays)) { return []; }
            $common_prefix_tokens = [];
            $smallest_number_of_elements = min(array_map("count", $list_of_token_arrays));
            for ($i = 0; $i < $smallest_number_of_elements; $i++) {
                $all_the_same = count(\NeoRename\NeoGlobal\array_unique_better(array_column($list_of_token_arrays, $i))) === 1;
                if ($all_the_same) { $common_prefix_tokens[] = $list_of_token_arrays[0][$i]; } else { break; }
            }
            return $common_prefix_tokens;
        };

        $common_suffix = function ($list_of_token_arrays) use ($common_prefix) {
            return array_reverse($common_prefix(array_map("array_reverse", $list_of_token_arrays)));
        };

        $normalize_common_parts = function ($list_of_token_arrays, $prefix_tokens, $suffix_tokens) {
            if (empty($list_of_token_arrays)) { return [$prefix_tokens, $suffix_tokens]; }
            $min_token_count = min(array_map("count", $list_of_token_arrays));
            $overlap = count($prefix_tokens) + count($suffix_tokens) - $min_token_count;
            if ($overlap > 0) { $suffix_tokens = array_slice($suffix_tokens, $overlap); }
            return [$prefix_tokens, $suffix_tokens];
        };

        $has_meaningful_shared_tokens = function ($tokens) use ($render_tokens) { return trim($render_tokens($tokens), " \t\n\r\0\x0B{}[],:\"") !== ""; };

        $deduplicated = [];
        foreach ($file_logs as $log_entry) {
            $msg_tokens = $tokenize_message($log_entry["message"]);
            $matched_group = false;

            foreach ($deduplicated as &$group) {
                $all_msgs = $group["token_lists"]; $all_msgs[] = $msg_tokens;
                $new_prefix = $common_prefix($all_msgs); $new_suffix = $common_suffix($all_msgs);
                [$new_prefix, $new_suffix] = $normalize_common_parts($all_msgs, $new_prefix, $new_suffix);
                if (!$has_meaningful_shared_tokens($new_prefix) && !$has_meaningful_shared_tokens($new_suffix)) { continue; }
                $group["prefix"] = $new_prefix; $group["suffix"] = $new_suffix; $group["token_lists"] = $all_msgs; $group["dates"][] = $log_entry["date"]; $group["count"]++; $group["raw_lines"][] = $log_entry["raw"];
                $matched_group = true; break;
            }
            unset($group);

            if (!$matched_group) { $deduplicated[] = ["prefix" => $msg_tokens, "suffix" => [], "token_lists" => [$msg_tokens], "dates" => [$log_entry["date"]], "count" => 1, "raw_lines" => [$log_entry["raw"]]]; }
        }

        $final_logs_this_file = [];
        foreach ($deduplicated as $group) {
            usort($group["dates"], function($a, $b) { return strcmp($b, $a); });
            $date_newest = $group["dates"][0];
            $date_oldest = end($group["dates"]);
            $duration = \NeoRename\NeoGlobal\timestamp_from_utc_date_string($date_newest) - \NeoRename\NeoGlobal\timestamp_from_utc_date_string($date_oldest);
            $new_prefix = $common_prefix($group["token_lists"]); $new_suffix = $common_suffix($group["token_lists"]);
            [$new_prefix, $new_suffix] = $normalize_common_parts($group["token_lists"], $new_prefix, $new_suffix);

            $middle_texts = [];
            foreach ($group["token_lists"] as $one_msg_tokens) {
                $prefix_len = count($new_prefix);
                $suffix_len = count($new_suffix);
                $middle_len = count($one_msg_tokens) - $prefix_len - $suffix_len;
                if ($middle_len < 0) { $middle_len = 0; }
                $middle = array_slice($one_msg_tokens, $prefix_len, $middle_len);
                $middle_texts[] = $render_tokens($middle);
            }

            $middle_texts = \NeoRename\NeoGlobal\array_unique_better($middle_texts);
            $middle_texts_non_empty = \NeoRename\NeoGlobal\array_filter_better($middle_texts, function ($middle_text) { return $middle_text !== ""; });
            if ($group["count"] === 1 || empty($middle_texts_non_empty)) {
                $final_message = $render_tokens($group["token_lists"][0]);
            } else {
                $prefix_str = $render_tokens($new_prefix);
                $suffix_str = $render_tokens($new_suffix);
                $curly_content_plain = implode(", ", $middle_texts_non_empty);

                $curly_content = '{' . (mb_strlen($curly_content_plain) > 100 ? mb_substr($curly_content_plain, 0, 100) . "..." : $curly_content_plain) . '}';

                $final_message = trim($prefix_str . $curly_content . $suffix_str);
            }

            $final_logs_this_file[] = ["date_newest" => $date_newest, "duration" => $duration, "count" => $group["count"], "file" => basename($full_path), "message" => $final_message, "raw" => implode("\n", $group["raw_lines"])];
        }

        $file_infos[] = ["file" => basename($full_path), "count" => array_sum(array_column($final_logs_this_file, "count")), "size" => $file_size_kb];

        usort($file_infos, function($a, $b) { return strcmp($a["file"], $b["file"]); });

        $all_logs = array_merge($all_logs, $final_logs_this_file);
    }

    usort($all_logs, function($a, $b) { return strcmp($b["date_newest"], $a["date_newest"]); });

    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>neoLog - Overview</title>

    <?php \NeoRename\NeoGlobal\backend_page_script_tag_start([]); ?> /* Own script tag instead of enqueue because it's our own HTML page */
        <?php echo \NeoRename\NeoGlobal\get_code_for_js_variables("backend") /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>;
    <?php \NeoRename\NeoGlobal\backend_page_script_tag_end(); ?>
    <?php \NeoRename\NeoGlobal\backend_page_style_tag_start([]); ?> /* Own style tag instead of enqueue because it's our own HTML page */
        body { font-family: Arial, sans-serif; }

        #logFileTable, #logEntriesTable { border-collapse: collapse; margin-bottom: 20px; }
        :is(#logFileTable, #logEntriesTable) :is(th, td) { padding: 5px 8px; white-space: nowrap; border: none; }
        :is(#logFileTable, #logEntriesTable) th { text-align: left; background-color: #005f99; color: white; }

        :is(#logFileTable, #logEntriesTable) tbody tr:nth-child(odd) { background-color: #e6f7ff; }
        :is(#logFileTable, #logEntriesTable) tbody tr:nth-child(even) { background-color: #ffffff; }

        :is(#logFileTable, #logEntriesTable) :is(th, td).right-aligned { text-align: right; }

        #logFileTable { width: 600px; }
        .warn-file, .warn-message { color: #c4a000; }
        .error-file, .error-message { color: #ff0000; }
        #logSearch { width: 300px; padding: 5px; margin-bottom: 10px; }
    <?php \NeoRename\NeoGlobal\backend_page_style_tag_end(); ?>
</head>
<body>

<?php
    if (!empty($warnings)) {
        foreach ($warnings as $w) { ?><p style="color:#c4a000;"><?php echo esc_html($w); ?></p><?php }
        echo "<br>";
    }
?>

<h1>neoLog Overview</h1>
<p>Currently reading max <?php echo esc_html($per_file_limit) ?> lines per file (<a href="<?php echo esc_url(\NeoRename\NeoGlobal\add_query_param(\NeoRename\NeoGlobal\request_uri(), "limit", 1000)); ?>">change to 1000</a>).</p>

<table id="logFileTable">
    <thead><tr><th>File</th><th class="right-aligned">Count</th><th class="right-aligned">Size</th></tr></thead>
    <tbody>
    <?php if (empty($file_infos)) { ?>
        <tr><td colspan="3">No log files found in directory <?php echo esc_html($log_dir); ?>.</td></tr>
    <?php } ?>
    <?php foreach ($file_infos as $fi) {
        $file_name = $fi["file"];
        $file_size = $fi["size"];
        $file_class = str_contains(strtolower($file_name), "warn") ? "warn-file" : (str_contains(strtolower($file_name), "error") ? "error-file" : "");
        $count_display = $fi["count"] . ($fi["count"] >= $per_file_limit ? " (limit)" : "");
        ?>
        <tr>
            <td class="<?php echo esc_attr($file_class) ?>"><a href="<?php echo esc_url(\NeoRename\NeoGlobal\cache_url("neo-log") . "/" . $file_name) ?>" target="_blank"><?php echo esc_html($file_name) ?></a></td>
            <td class="right-aligned"><?php echo esc_html($count_display) ?></td>
            <td class="right-aligned"><?php echo esc_html($file_size) ?> KB</td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<p>All log files are limited to <?php echo esc_html(log_file_size_limit_mb()) ?>&nbsp;MB. Older logs are automatically deleted.</p>

<button onclick="window.location.href = '<?php echo esc_url(\NeoRename\NeoGlobal\add_query_param(\NeoRename\NeoGlobal\request_uri(), "download-all-logs", "1")); ?>'">Download All Logs</button>
<button onclick="window.deleteLogs()">Delete All Logs</button><br><br>
<?php \NeoRename\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
    import { fetchEndpoint } from '<?php echo esc_url(\NeoRename\NeoGlobal\plugin_url() . "/_global--endpoint.js"); ?>';
    import { extractJson } from '<?php echo esc_url(\NeoRename\NeoGlobal\plugin_url() . "/_global--extract-json.js"); ?>';
    import { reloadPage } from '<?php echo esc_url(\NeoRename\NeoGlobal\plugin_url() . "/_global-reload-page.js"); ?>';
    window.deleteLogs = async function() {
        if (!confirm("Are you sure you want to delete all logs? This action cannot be undone.")) { return; }
        try {
            let response = await fetchEndpoint("/wp-json/neo/delete-logs", { method: "POST" }).then(extractJson);
            if (response === "OKAY") { reloadPage(); } else { alert("Error deleting logs: " + response); }
        } catch (error) {
            alert("Error deleting logs: " + error.message);
        }
    };
<?php \NeoRename\NeoGlobal\backend_page_script_tag_end(); ?>

<input type="text" id="logSearch" placeholder="Search ..." />

<table id="logEntriesTable">
    <thead><tr><th>Newest Date</th><th>Timespan</th><th>Count</th><th>File</th><th>Message</th></tr></thead>
    <tbody>
    <?php foreach ($all_logs as $log) {
        $f_class = str_contains(strtolower($log["file"]),    "warn") ? "warn-file"    : (str_contains(strtolower($log["file"]),    "error") ? "error-file"    : "");
        $m_class = str_contains(strtolower($log["message"]), "warn") ? "warn-message" : (str_contains(strtolower($log["message"]), "error") ? "error-message" : "");
        ?>
        <tr>
            <td class="right-aligned"><?php echo esc_html($log["date_newest"]); ?></td>
            <td class="right-aligned"><?php
                $days    = floor($log["duration"] / 86400); $remaining = $log["duration"] % 86400;
                $hours   = floor($remaining / 3600); $remaining = $remaining % 3600;
                $minutes = floor($remaining / 60);
                $duration_str = "";
                if ($days > 0)    { $duration_str .= $days . "d "; }
                if ($hours > 0)   { $duration_str .= $hours . "h "; }
                if ($minutes > 0) { $duration_str .= $minutes . "m"; }
                echo esc_html(trim($duration_str) ?: "0m");
            ?></td>
            <td class="right-aligned"><?php echo esc_html($log["count"]) ?></td>
            <td class="<?php echo esc_attr($f_class) ?>"><?php echo esc_html($log["file"]) ?></td>
            <td class="<?php echo esc_attr($m_class) ?>">
                <span onclick="let icon = this; navigator.clipboard.writeText(this.getAttribute('data-raw')).then(() => icon.textContent = '✅').then(() => setTimeout(() => icon.textContent = '📋', 2000)).catch(err => console.error('Failed to copy log:', err));"
                      data-raw="<?php echo esc_attr($log["raw"]); ?>"
                      style="cursor: pointer; user-select: none; margin-right: 12px;">📋</span><?php
                echo esc_html($log["message"]);
            ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<?php \NeoRename\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>

let inputField = document.getElementById("logSearch");
let tableBody = document.getElementById("logEntriesTable").getElementsByTagName("tbody")[0];
inputField.addEventListener("keyup", () => {
    let filter = inputField.value.toLowerCase();
    let rows = tableBody.getElementsByTagName("tr");
    for (let i = 0; i < rows.length; i++) {
        let cells = rows[i].getElementsByTagName("td");
        let rowMatch = false;
        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().includes(filter)) {
                rowMatch = true;
                break;
            }
        }
        rows[i].style.display = rowMatch ? "" : "none";
    }
});
<?php \NeoRename\NeoGlobal\backend_page_script_tag_end(); ?>

</body>
</html>
<?php
});

\NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/delete-logs", "POST", fn () => \NeoRename\NeoGlobal\current_user_can__neo_log__view(), function () {
    \NeoRename\NeoGlobal\delete_all(\NeoRename\NeoGlobal\cache_path("neo-log"));
    return "OKAY";
});
