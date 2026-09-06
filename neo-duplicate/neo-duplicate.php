<?php
/*
* Plugin Name: neo Duplicate
* Plugin URI: https://neo-wp.com/?plugin-slug=neo-duplicate&edition=wporg&ref=entry-file
* Description: Duplicate images for SEO for page-specific ALT texts.
* Version: 26.8.31
* Requires PHP: 8.0
* Requires at least: 6.2
* Author: neoWP
* Author URI: https://neo-wp.com
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Edition: wporg
* Slug: neo-duplicate
* Filename: neo-duplicate.26.8.31.zip
* Date: 2026-08-31 17:19:44 (Europe/Berlin)
* Country of Origin: Made in Germany
*/
// ### Plugin entry file providing central plugin metadata and the PHP autoloader for all neoPlugins. ###
// Central provider functions for plugin metadata
namespace NeoDuplicate\NeoGlobal {
    // Abort if this plugin is loaded directly.
    if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

    // Abort if this plugin is loaded twice with different names.
    if (!function_exists("\\" . __NAMESPACE__ . "\\" . "dummy_function")) { function dummy_function() { }
        function str_contains($h, $n)    { return $n === "" || strpos($h,$n) !== false; }
        function str_starts_with($h, $n) { return $n === "" || strncmp($h,$n,strlen($n)) === 0; }
        function str_ends_with($h, $n)   { return $n === "" || substr($h,-strlen($n)) === $n; }
        // Global plugin infos
        function plugin_slug()                { return read_plugin_infos()["plugin_slug"]; }
        function plugin_edition()             { return read_plugin_infos()["plugin_edition"]; }
        function plugin_version()             { return read_plugin_infos()["plugin_version"]; }
        function plugin_contributors()        { return read_plugin_infos()["plugin_contributors"]; }
        function plugin_timestamp()           { return read_plugin_infos()["plugin_timestamp"]; }
        function plugin_namespace_prefix()    { return read_plugin_infos()["plugin_namespace_prefix"]; }
        function plugin_min_php_ver()         { return read_plugin_infos()["plugin_min_php_ver"]; }
        function plugin_min_wp_ver()          { return read_plugin_infos()["plugin_min_wp_ver"]; }
        function plugin_license()             { return read_plugin_infos()["plugin_license"]; }
        function plugin_license_uri()         { return read_plugin_infos()["plugin_license_uri"]; }
        function plugin_blocked_os_families() { return read_plugin_infos()["plugin_blocked_os_families"]; }
        function plugin_author()              { return read_plugin_infos()["plugin_author"]; }
        function plugin_author_uri()          { return read_plugin_infos()["plugin_author_uri"]; }
        function plugin_author_a_tag()        { return read_plugin_infos()["plugin_author_a_tag"]; }
        function plugin_author_profile()      { return read_plugin_infos()["plugin_author_profile"]; }
        function plugin_entry_file_path()     { return __FILE__; }
        function plugin_settings_page_slug()  { return "neowp"; }

        function read_plugin_infos($force_refresh = false) {
            static $plugin_info_cache = null;
            if ($force_refresh) { $plugin_info_cache = null; }
            $plugin_info_cache ??= json_decode(file_get_contents(preg_replace('/\.php$/', ".json", __FILE__)), true);
            return $plugin_info_cache;
        }
    }
}

// Core plugin loader
namespace NeoCore {
    // Only load the core logic once.
    if (!class_exists("\\" . __NAMESPACE__ . "\\" . "NeoCoreMaster")) {
        class NeoCoreMaster {
            private static $master_function  = null;
            private static $newest_timestamp = null;

            public static function register_master_function($timestamp, $function) {
                if (!($timestamp > 1672531200 && $timestamp < 10413792000)) { throw new \InvalidArgumentException("Timestamp is out of bounds year 2023 to 2300."); }
                if (__NAMESPACE__ !== "NeoCore") { throw new \LogicException("The namespace NeoCore must never be renamed."); }
                if (!(NeoCoreMaster::$newest_timestamp === null || NeoCoreMaster::$newest_timestamp < $timestamp)) { return; }
                NeoCoreMaster::$newest_timestamp = $timestamp;
                NeoCoreMaster::$master_function  = $function;
            }

            public static function run_master_function() {
                if (NeoCoreMaster::$master_function === null) { return; }
                static $first_run = true; if (!$first_run) { return; } $first_run = false;
                (NeoCoreMaster::$master_function)();
            }

            public static function _run_master_function_after_plugins_loaded() {
                add_action("plugins_loaded", function () { self::run_master_function(); });
            }
        }
        NeoCoreMaster::_run_master_function_after_plugins_loaded();
        // Globals scoped to neoPlugins (safely scoped using a PHP namespace)
        class NeoSuperGlobal { public static $globals = []; }
    }
}

// Autoloader for all PHP files
namespace NeoDuplicate\NeoEntrypoint {
    // Abort if this plugin is loaded twice with different names.
    if (!function_exists("\\" . __NAMESPACE__ . "\\" . "dummy_function")) { function dummy_function() { }
        // Suppress plugin on error to ensure that the website is stable independent of the plugin
        function suppress_plugin($message) {
            setcookie("neosuppress", "true", time() + (20), "/"); $_COOKIE["neosuppress"] = "true";
            wp_print_inline_script_tag("document.cookie = \"neosuppress=true; path=/; max-age=20\";", ["type" => "module"]);

            update_option("neo_suppress_message", $message);
        }

        function suppress_plugin_on_error($callback) {
            try { return $callback(); }
            catch (\Throwable $e) {
                $error_message = $e->getMessage() . " " . "[" . basename($e->getFile()) . ":" . $e->getLine() . "]";
                if (\NeoDuplicate\NeoGlobal\str_contains($error_message, "#ignoreNeoSuppress")) { throw $e; }
                if (defined("WP_CLI") && WP_CLI) { throw $e; }
                try {
                    if (!($e instanceof \NeoDuplicate\NeoGlobal\GlobalException)) {
                        if (function_exists('\NeoDuplicate\NeoGlobal\global_warn')) { \NeoDuplicate\NeoGlobal\global_warn_with_module_name("neowp", "PHP Error in plugin: $error_message"); }
                    }
                } catch (\Throwable $inner_e) {}
                suppress_plugin($error_message);

                if (file_exists(__DIR__ . "/_global--error-message-page-only-debug.php")) { (fn () => require_once(__DIR__ . "/_global--error-message-page-only-debug.php"))(); \NeoDuplicate\NeoGlobal\show_better_error_message_page($e); }

                throw $e;
            }
        }

        function get_plugin_load_time() { static $load_time = null; return $load_time ??= microtime(true); } get_plugin_load_time();

        // Main plugin code
        suppress_plugin_on_error(function () {
            // Temporary plugin deactivation
            $query_string = sanitize_text_field(wp_unslash($_SERVER["QUERY_STRING"] ?? ""));
            if (\NeoDuplicate\NeoGlobal\str_contains($query_string, "neosuppress")) {
                suppress_plugin("Plugin is suppressed by ?neosuppress in the URL.");
            }
            if (\NeoDuplicate\NeoGlobal\str_contains($query_string, "neoundosuppress")) {
                setcookie("neosuppress", "", time() - 3600, "/");
                unset($_COOKIE["neosuppress"]);
            }
            $neo_suppress_cookie = sanitize_text_field(wp_unslash($_COOKIE["neosuppress"] ?? ""));
            if (!empty($neo_suppress_cookie)) {
                if (!isset(\NeoCore\NeoSuperGlobal::$globals["suppress_message_shown"])) { \NeoCore\NeoSuperGlobal::$globals["suppress_message_shown"] = true;
                    add_action("admin_notices", function () { ?><div class="notice notice-error"><p>neoPlugins are temporarily suppressed (20s). Reason: <?php echo esc_html(get_option("neo_suppress_message")) ?> <a href="<?php echo esc_url(add_query_arg("neoundosuppress", "", remove_query_arg("neosuppress"))) ?>">Re-enable now</a></p></div><?php });
                    add_action("wp_footer", function () { ?><div style="position: fixed; bottom: 0; left: 0; right: 0; background: #f00; color: #fff; padding: 10px;">neoPlugins are temporarily suppressed (20s). Reason: <?php echo esc_html(get_option("neo_suppress_message")) ?> <a style="text-decoration:underline;" href="<?php echo esc_url(add_query_arg("neoundosuppress", "", remove_query_arg("neosuppress"))) ?>">Re-enable now</a></div><?php });
                }

                if (\NeoDuplicate\NeoGlobal\str_contains($query_string, "page=" . \NeoDuplicate\NeoGlobal\plugin_settings_page_slug())) { add_action("init", function () { wp_safe_redirect(admin_url()); exit; }); }

                return;
            }
            if (empty($neo_suppress_cookie)) { delete_option("neo_suppress_message"); }

            // Prerequisites for plugin execution
            $neo_load_error_message = null;
            if (in_array(PHP_OS_FAMILY, \NeoDuplicate\NeoGlobal\plugin_blocked_os_families()))                { $neo_load_error_message = "neoPlugin is inactive because it is not supported on " . PHP_OS_FAMILY . "."; }
            if (!version_compare(PHP_VERSION, \NeoDuplicate\NeoGlobal\plugin_min_php_ver(), ">="))            { $neo_load_error_message = "neoPlugin is inactive because PHP is too old (" . PHP_VERSION . " < " . \NeoDuplicate\NeoGlobal\plugin_min_php_ver() . ")."; }
            if (!version_compare(get_bloginfo("version"), \NeoDuplicate\NeoGlobal\plugin_min_wp_ver(), ">=")) { $neo_load_error_message = "neoPlugin is inactive because WP is too old (" . get_bloginfo("version") . " < " . \NeoDuplicate\NeoGlobal\plugin_min_wp_ver() . ")."; }

            if (is_multisite()) { $neo_load_error_message = 'neoPlugin is inactive because WordPress Multisite is not supported. Contact us to speed up development in this area. <a href="https://neo-wp.com/contact/?ref=multisite-error" target="_blank" rel="noopener noreferrer">https://neo-wp.com/contact/</a>'; }
            if (!extension_loaded("mbstring")) { $neo_load_error_message = "neoPlugin is inactive because the PHP mbstring extension is not loaded. Please enable mbstring in your hosting environment."; }

            if (!((is_dir(untrailingslashit(WP_CONTENT_DIR) . "/cache/") && is_writable(untrailingslashit(WP_CONTENT_DIR) . "/cache/")) || (!is_dir(untrailingslashit(WP_CONTENT_DIR) . "/cache/") && is_writable(untrailingslashit(WP_CONTENT_DIR))))) { $neo_load_error_message = "neoPlugin is inactive because the cache folder is not writable. Please make the folder " . untrailingslashit(WP_CONTENT_DIR) . "/cache/" . " writable."; } /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */ /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable */

            if ($neo_load_error_message) {
                if (defined("WP_CLI") && WP_CLI) { \WP_CLI::error($neo_load_error_message); }
                else { add_action("admin_notices", function() use ($neo_load_error_message) { echo '<div class="notice notice-error"><p>' . wp_kses_post($neo_load_error_message) . '</p></div>'; }); }
                return;
            }

            // Prepare autoloader for plugin files
            $global_files = [];
            $module_files = [];
            function _neo_entry_function_get_module_name($rel_path) {
                if (\NeoDuplicate\NeoGlobal\str_starts_with($rel_path, "_global-")) { return "neo-global"; }
                if ($rel_path === "live-update" . ".php")   { return "neo-live-update"; }
                return preg_split('/(--|\/)/', explode('.', $rel_path)[0])[0];
            }
            foreach (scandir(dirname(__FILE__)) as $plugin_file_name) {
                if ($plugin_file_name === "." || $plugin_file_name === "..")             { continue; }
                if (!is_file(dirname(__FILE__) . "/" . $plugin_file_name))               { continue; }
                if (!\NeoDuplicate\NeoGlobal\str_ends_with($plugin_file_name, ".php"))                { continue; }
                if ($plugin_file_name === "index.php")                                   { continue; }
                if ($plugin_file_name === basename(\NeoDuplicate\NeoGlobal\plugin_entry_file_path())) { continue; }
                if (\NeoDuplicate\NeoGlobal\str_starts_with($plugin_file_name, "_global-")) { $global_files[] = $plugin_file_name; }
                else { $module_files[_neo_entry_function_get_module_name($plugin_file_name)][] = $plugin_file_name; }
            }
            if (isset($module_files["neo-website"])) { unset($module_files["neo-website"]); } if (isset($module_files["neo-website-neowp"])) { unset($module_files["neo-website-neowp"]); }
            sort($global_files); foreach (array_keys($module_files) as $module_name) { sort($module_files[$module_name]); }
            $global_files = array_values(array_unique(array_merge(["_global--global-values.php", "_global--hook.php", "_global--exceptions.php"], $global_files)));

            \NeoCore\NeoSuperGlobal::$globals["active_plugins"] ??= [];
            \NeoCore\NeoSuperGlobal::$globals["active_plugins"][] = [
                "slug" => \NeoDuplicate\NeoGlobal\plugin_slug(), "edition" => \NeoDuplicate\NeoGlobal\plugin_edition(),
                "version" => \NeoDuplicate\NeoGlobal\plugin_version(),
                "namespace_prefix" => \NeoDuplicate\NeoGlobal\plugin_namespace_prefix(),
                "plugin_path" => dirname(__FILE__),
                "plugin_entry_file_path" => \NeoDuplicate\NeoGlobal\plugin_entry_file_path(),
                "global_files" => $global_files,
                "module_files" => $module_files,
                "existing_modules" => array_keys($module_files),
            ];

            // neoPlugin priorisation if multipe neoPlugins are installed
            function master_function($is_special_call_for_activation_or_uninstallation = false) {
                suppress_plugin_on_error(function () use ($is_special_call_for_activation_or_uninstallation) {
                    $live_update_time_before = microtime(true);
                    if (file_exists(dirname(__FILE__) . "/" . "live-update" . ".php")) { (fn () => require_once(dirname(__FILE__) . "/" . "live-update" . ".php"))(); }
                    $live_update_time_after = microtime(true);

                    (fn () => require_once(dirname(__FILE__) . "/" . "_global--global-values.php"))(); (fn () => require_once(dirname(__FILE__) . "/" . "_global--hook.php"))();
                    \NeoDuplicate\NeoGlobal\performance_checkpoint("live update", time: $live_update_time_before, is_start: true);
                    \NeoDuplicate\NeoGlobal\performance_checkpoint("live update", time: $live_update_time_after,  is_end: true);
                    \NeoDuplicate\NeoGlobal\performance_checkpoint("plugin-load", is_start: true);

                    usort(\NeoCore\NeoSuperGlobal::$globals["active_plugins"], function ($plugin_a, $plugin_b) {
                        $get_score = function ($a, $b) {                                                                           $score  =     0;
                            if ($a["edition"] === "dev")                                                                         { $score += 10000; }
                            if ($a["edition"] === "beta")                                                                        { $score +=  1000; }
                            if ($a["edition"] === "pro")                                                                         { $score +=   100; }
                            if (version_compare(str_replace("l", "p", $a["version"]), str_replace("l", "p", $b["version"])) > 0) { $score +=    10; }
                            if ($a["edition"] === "full")                                                                        { $score +=     1; }
                            return $score;
                        };
                        return $get_score($plugin_b, $plugin_a) <=> $get_score($plugin_a, $plugin_b);
                    });

                    // Autoloader for plugin files
                    foreach (\NeoCore\NeoSuperGlobal::$globals["active_plugins"] as $plugin) {
                        foreach ($plugin["global_files"] as $global_file) {
                            if (in_array($plugin["plugin_path"] . "/" . $global_file, get_included_files())) { continue; }
                            \NeoDuplicate\NeoGlobal\performance_checkpoint("load $global_file", is_start: true);
                            (fn () => require_once($plugin["plugin_path"] . "/" . $global_file))();
                            \NeoDuplicate\NeoGlobal\performance_checkpoint("load $global_file", is_end: true);
                        }
                    }

                    if (\NeoDuplicate\NeoGlobal\array_filter_better(\NeoCore\NeoSuperGlobal::$globals["active_plugins"], fn ($active_plugin) => array_key_exists("neo-redirect", $active_plugin["module_files"]))) {
                        foreach (\NeoCore\NeoSuperGlobal::$globals["active_plugins"] as &$active_plugin_ref) { unset($active_plugin_ref["module_files"]["neo-rename-redirect"]); }
                    }

                    if (\NeoDuplicate\NeoGlobal\array_filter_better(\NeoCore\NeoSuperGlobal::$globals["active_plugins"], fn ($active_plugin) => array_key_exists("neo-manager-installer", $active_plugin["module_files"]))) {
                        foreach (\NeoCore\NeoSuperGlobal::$globals["active_plugins"] as &$active_plugin_ref) {
                            foreach ($active_plugin_ref["module_files"] as $module_slug => $module_files) {
                                foreach (\NeoDuplicate\NeoGlobal\option__neo_manager_installer__suppressed_plugins_list() as $suppressed_plugin_slug) {
                                    if (\NeoDuplicate\NeoGlobal\str_starts_with($module_slug, $suppressed_plugin_slug)) {
                                        unset($active_plugin_ref["module_files"][$module_slug]);
                                    }
                                }
                            }
                        }
                    }

                    \NeoCore\NeoSuperGlobal::$globals["executed_modules"] ??= [];
                    foreach (\NeoCore\NeoSuperGlobal::$globals["active_plugins"] as $active_plugin) {
                        foreach ($active_plugin["module_files"] as $module_slug => $module_files) {
                            if (in_array($module_slug, \NeoCore\NeoSuperGlobal::$globals["executed_modules"])) { continue; }
                            \NeoCore\NeoSuperGlobal::$globals["executed_modules"][] = $module_slug;
                            foreach ($module_files as $module_file) {
                                if (in_array($active_plugin["plugin_path"] . "/" . $module_file, get_included_files())) { continue; }
                                \NeoDuplicate\NeoGlobal\performance_checkpoint("load $module_file", is_start: true);
                                (fn () => require_once($active_plugin["plugin_path"] . "/" . $module_file))();
                                \NeoDuplicate\NeoGlobal\performance_checkpoint("load $module_file", is_end: true);
                            }
                        }
                    }

                    \NeoCore\NeoSuperGlobal::$globals["interface_system_ready"] = true;

                    if (!$is_special_call_for_activation_or_uninstallation) { do_action("neo_init"); } /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound */ /* An immutable neoPlugin hook registered globally to provide a reliable and consistent integration interface for all plugins. */

                    \NeoDuplicate\NeoGlobal\performance_checkpoint("plugin-load", is_end: true);
                });
            }

            \NeoCore\NeoCoreMaster::register_master_function(\NeoDuplicate\NeoGlobal\plugin_timestamp(), __NAMESPACE__ . "\\master_function");

            $postload_self = function () {
                if (\NeoDuplicate\NeoGlobal\plugin_edition() === "dev") { return; }

                master_function(is_special_call_for_activation_or_uninstallation: true);
            };

            add_action("activate_"   . plugin_basename(__FILE__), function () use ($postload_self) { $postload_self(); }, 9);
            add_action("uninstall_"  . plugin_basename(__FILE__), function () use ($postload_self) { $postload_self(); }, 9);
            add_action("deactivate_" . plugin_basename(__FILE__), function () { ;},                9);
        });

        function get_neo_active_plugins() { return \NeoCore\NeoSuperGlobal::$globals["active_plugins"] ?? []; }
    }
}
