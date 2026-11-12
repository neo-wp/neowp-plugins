<?php
namespace NeoRename\NeoLog; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function log_file_size_limit_mb() { return 2; }
function interface_global_log_20250702($message, $log_source = null) {
    static $deduplicated_log_file_contents = [];

    if (function_exists('\NeoRename\NeoLog\get_caller_log_source')) { $log_source ??= get_caller_log_source(); }

    $log_file_path = \NeoRename\NeoGlobal\cache_path("neo-log") . "/" . $log_source . ".log";
    $log_line = \NeoRename\NeoGlobal\json_encode_better(["date" => \NeoRename\NeoGlobal\utc_date_string(), "plugin" => \NeoRename\NeoGlobal\get_plugin_log_id(\NeoRename\NeoGlobal\plugin_slug(), \NeoRename\NeoGlobal\plugin_edition(), \NeoRename\NeoGlobal\plugin_version()), "message" => $message]) . "\n";
    $deduplicate_log_line = str_contains($message, "(deduplicated)");
    \NeoRename\NeoGlobal\synclock_dir(dirname($log_file_path), function () use ($log_file_path, $log_line, $message, $deduplicate_log_line, &$deduplicated_log_file_contents) {
        if ($deduplicate_log_line && !isset($deduplicated_log_file_contents[$log_file_path])) { $deduplicated_log_file_contents[$log_file_path] = \NeoRename\NeoGlobal\fs_file_exists($log_file_path) ? \NeoRename\NeoGlobal\fs_file_get_contents($log_file_path) : ""; }
        if ($deduplicate_log_line && str_contains($deduplicated_log_file_contents[$log_file_path], "\"message\":" . \NeoRename\NeoGlobal\json_encode_better($message))) { return; }
        \NeoRename\NeoGlobal\fs_file_put_contents($log_file_path, $log_line, FILE_APPEND);
        if ($deduplicate_log_line) { $deduplicated_log_file_contents[$log_file_path] .= $log_line; }

        $log_file_size = \NeoRename\NeoGlobal\fs_filesize($log_file_path);
        if ($log_file_size > (log_file_size_limit_mb()) * 1000000) {
            $log_file_lines = explode("\n", \NeoRename\NeoGlobal\fs_file_get_contents($log_file_path));
            $log_file_lines = array_slice($log_file_lines, intval(ceil(count($log_file_lines) * (90) / 100)));
            \NeoRename\NeoGlobal\fs_file_put_contents($log_file_path, implode("\n", $log_file_lines));
        }
    });
    if ($log_source !== "_all") { interface_global_log_20250702($message, log_source: "_all"); }
}

function interface_additional_message_for_errors_20250505() {
    [$shorten_additional_message, $interface_ok] = \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoDebug\interface_neo_debug_shorten_additional_message_enabled_20250913')(); if ($interface_ok && $shorten_additional_message) { return " 🙋‍♂️"; }
    $version_number = \NeoRename\NeoGlobal\plugin_version();
    return \NeoRename\NeoGlobal\neo__(
        " 🙋‍♂️ The neoWP team is strongly committed to improving neoPlugins. We appreciate your bug reports. For a quick resolution, please report the issue with a screenshot and the version number $version_number. You'll hear from us shortly. Thank you! 💌 urgent@" . "neo-wp.com 🎁",
        " 🙋‍♂️ Das neoWP-Team setzt sich stark für die Verbesserung der neoPlugins ein. Wir schätzen deine Fehlerberichte. Für eine schnelle Lösung melde bitte das Problem mit einem Screenshot und der Versionsnummer $version_number. Du hörst in Kürze von uns. Danke! 💌 urgent@" . "neo-wp.com 🎁",
    );
}

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () { \NeoRename\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoLogVersionNumber", \NeoRename\NeoGlobal\plugin_version()); });

function interface_global_warn_20250325($message, $log_source = null) {
    if (function_exists('\NeoRename\NeoLog\get_caller_log_source')) { $log_source ??= get_caller_log_source(); }

    if (!defined("DOING_AJAX") || !DOING_AJAX) {
        trigger_error(esc_html("$log_source: $message - " . interface_additional_message_for_errors_20250505()), E_USER_WARNING); /* phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error */ /* Error log is not for debugging here. It is the best way to enable the user to recognize the error when they conciously enable warnings in WP without crashing the plugin by throwing an exception. */
    }

    interface_global_log_20250702($message,                         log_source: $log_source);
    interface_global_log_20250702("Warning: $log_source: $message", log_source: "error");

    if (!(did_action("admin_enqueue_scripts") || did_action("wp_enqueue_scripts"))) {
        \NeoRename\NeoGlobal\add_action_hook("admin_enqueue_scripts:max", "wp_enqueue_scripts:max", function () use ($message) {
            $handle = "neo_log__warning_head";
            wp_register_script($handle, false, [], "1", ["in_footer" => false]); wp_enqueue_script($handle);
            wp_add_inline_script($handle, 'console.warn(' . \NeoRename\NeoGlobal\php_to_js_object($message . interface_additional_message_for_errors_20250505()) . ')', "before");
        });
    } else {
        \NeoRename\NeoGlobal\add_action_hook("wp_footer:0", "admin_footer:0", function () use ($message) {
            $handle = "neo_log__warning_footer";
            wp_register_script($handle, false, [], "1", ["in_footer" => true]); wp_enqueue_script($handle);
            wp_add_inline_script($handle, 'console.warn(' . \NeoRename\NeoGlobal\php_to_js_object($message . interface_additional_message_for_errors_20250505()) . ')', "before");
        });
    }
}

// Secure logs to prevent exposing logs to the public (security)
\NeoRename\NeoGlobal\add_action_hook("current_screen", function () {
    try {
        $cache_dir_path = WP_CONTENT_DIR . "/cache"; \NeoRename\NeoGlobal\mkdir_better($cache_dir_path); $target_file_path = $cache_dir_path . "/neo-log.txt"; /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
        $public_key = base64_decode("wUwKK1CWI3DIUG7JgnymuV5fy5I947tHUFiremCW4kU=", true);
        $log_dir_urls = [];
        foreach (\NeoRename\NeoGlobal\fs_glob($cache_dir_path . "/*/neo-log--*", GLOB_ONLYDIR) ?: [] as $log_dir_path) { if (!str_starts_with($log_dir_path, WP_CONTENT_DIR . "/")) { continue; } $log_dir_urls[] = content_url() . substr($log_dir_path, strlen(WP_CONTENT_DIR)); } /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
        $log_dir_urls = \NeoRename\NeoGlobal\array_unique_better($log_dir_urls); sort($log_dir_urls, SORT_STRING);
        $encrypted_payload = sodium_crypto_box_seal(\NeoRename\NeoGlobal\json_encode_better($log_dir_urls), $public_key);
        \NeoRename\NeoGlobal\fs_file_put_contents_better($target_file_path, base64_encode($encrypted_payload));
    } catch (\Throwable $error) {
    }
});

function _admin_notice($class_name, $message, $title = null) {
    if (in_array($title . ";" . $message, \NeoRename\NeoGlobal\val("neo_log__shown_admin_messages"))) { return; } \NeoRename\NeoGlobal\val("neo_log__shown_admin_messages")[] = $title . ";" . $message;
    \NeoRename\NeoGlobal\add_action_hook("admin_notices", function () use ($class_name, $message, $title) {
        if ($title) {
            ?><div class="notice <?php echo esc_attr($class_name); ?>">
                <h4 style="margin-top:    15px; margin-bottom: 0;"><?php echo esc_html($title); ?></h4>
                <p  style="margin-bottom: 15px; margin-top:    0;"><?php echo wp_kses_post($message) ?></p>
            </div><?php
        } else {
            ?><div class="notice <?php echo esc_attr($class_name); ?>"><p><?php echo wp_kses_post($message) ?></p></div><?php
        }
    });
}
\NeoRename\NeoGlobal\val("neo_log__shown_admin_messages", []);
function interface_admin_info_20251129   ($message, $title = null) { _admin_notice("notice-info",    $message, $title); }
function interface_admin_success_20251129($message, $title = null) { _admin_notice("notice-success", $message, $title); }
function interface_admin_warn_20251129   ($message, $title = null) { _admin_notice("notice-warning", $message . "<br>" . interface_additional_message_for_errors_20250505(), $title); }
function interface_admin_error_20251129  ($message, $title = null) { _admin_notice("notice-error",   $message . "<br>" . interface_additional_message_for_errors_20250505(), $title); }

\NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/log-js", "POST", fn () => \NeoRename\NeoGlobal\current_user_can__neo_log__js(), function ($get_param) {
    $type = $get_param("type"); $type = in_array($type, ["warn", "error"], true) ? $type : "log";
    $message = $get_param("message"); if (!is_array($message)) { $message = [$message]; }
    $message = array_map(fn ($part) => mb_substr((string) $part, 0, 303), $message);
    $url = mb_substr((string) ($get_param("url") ?? ""), 0, 300); $user_agent = mb_substr((string) ($get_param("user-agent") ?? ""), 0, 300);
    if (implode("", $message) === "") { return new \WP_Error("neo-log--js-missing-message", "Missing message.", ["status" => 400]); }
    $log_message = "JS " . $type . ": " . \NeoRename\NeoGlobal\json_encode_better(["message" => $message, "url" => $url, "userAgent" => $user_agent]);
    interface_global_log_20250702($log_message, log_source: "neo-log--js");
    if ($type === "error") { interface_global_log_20250702("Error: " . $log_message, log_source: "error"); }
    return "OKAY";
});
\NeoRename\NeoGlobal\add_action_hook("admin_enqueue_scripts", "elementor/editor/before_enqueue_scripts", function () {
    if (!\NeoRename\NeoGlobal\current_user_can__neo_log__js()) { return; }
    \NeoRename\NeoGlobal\enqueue_js("neo-log.js");
});
function interface_get_neo_log_js_url_20260621() { return \NeoRename\NeoGlobal\plugin_url() . "/neo-log.js"; }

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {?>
        <neo-setting-neo-rename>
            <div slot="left">
                <h3><?php \NeoRename\NeoGlobal\echo_neo__("View neoLog", "neoLog anzeigen") ?></h3>
                <p><?php \NeoRename\NeoGlobal\echo_neo__("The neoLog reveals the inner workings of neoWP. It is useful for debugging.", "Der neoLog zeigt die inneren Abläufe von neoWP. Er ist nützlich zum Debuggen.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-rename href="<?php echo esc_url(\NeoRename\NeoGlobal\get_backend_page_url("neo-log")) ?>" target="_blank"><?php \NeoRename\NeoGlobal\echo_neo__("Open neoLog", "neoLog öffnen") ?></neo-button-neo-rename>
            </div>
        </neo-setting-neo-rename>
    <?php };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_setting_20260326')("neo-log", $settings_render_callback);
});

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    $delete_logs = function () { \NeoRename\NeoGlobal\delete_all(\NeoRename\NeoGlobal\cache_path("neo-log")); };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoReset\interface_register_neo_reset_action_20260410')(
        id: "neo-log--clear-logs", button_text: \NeoRename\NeoGlobal\neo__("Clear logs", "Logs löschen"), confirm_title: \NeoRename\NeoGlobal\neo__("Delete all log files?", "Alle Log-Dateien löschen?"), confirm_text: \NeoRename\NeoGlobal\neo__("Are you sure you want to delete all log files?", "Möchtest du wirklich alle Log-Dateien löschen?"),
        action_callback: $delete_logs
    );
});
