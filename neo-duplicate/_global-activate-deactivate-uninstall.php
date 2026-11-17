<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Carefully implemented activation, deactivation and uninstall hooks with late cache cleanup and selective deletion of neo_ options.
// Plugin activation
register_activation_hook(\NeoDuplicate\NeoGlobal\plugin_entry_file_path(), function () {
    \NeoDuplicate\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Activate plugin " . \NeoDuplicate\NeoGlobal\plugin_slug() . "-" . \NeoDuplicate\NeoGlobal\plugin_edition());
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoFreemius\interface_freemius_activate_hook_20260210')(\NeoDuplicate\NeoGlobal\plugin_slug(), \NeoDuplicate\NeoGlobal\plugin_edition(), \NeoDuplicate\NeoGlobal\plugin_version());
});

// Plugin deactivation
register_deactivation_hook(\NeoDuplicate\NeoGlobal\plugin_entry_file_path(), function () {
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoFreemius\interface_freemius_deactivate_hook_20260210')(\NeoDuplicate\NeoGlobal\plugin_slug(), \NeoDuplicate\NeoGlobal\plugin_edition(), \NeoDuplicate\NeoGlobal\plugin_version());
    \NeoDuplicate\NeoGlobal\add_action_hook("shutdown:max", function() {
        \NeoDuplicate\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Deactivate plugin " . \NeoDuplicate\NeoGlobal\plugin_slug() . "-" . \NeoDuplicate\NeoGlobal\plugin_edition());
        clear_plugin_cache(delete_all: false);
        \NeoDuplicate\NeoGlobal\flush_all_third_party_caches();
    });
});

// Plugin update
\NeoDuplicate\NeoGlobal\add_filter_hook("upgrader_post_install", function ($response, $hook_extra, $result) {
    if (!is_array($result) || !isset($result["destination_name"])) { return $response; }
    if (!($result["destination_name"] === \NeoDuplicate\NeoGlobal\get_bundle_slug(\NeoDuplicate\NeoGlobal\plugin_slug(), \NeoDuplicate\NeoGlobal\plugin_edition()))) { return $response; }
    \NeoDuplicate\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Clear cache because of plugin update " . \NeoDuplicate\NeoGlobal\plugin_slug() . "-" . \NeoDuplicate\NeoGlobal\plugin_edition());
    clear_plugin_cache(delete_all: false);
    \NeoDuplicate\NeoGlobal\flush_all_third_party_caches();
    return $response;
});

// Plugin uninstall
function uninstall() {
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoFreemius\interface_freemius_uninstall_hook_20260210')(\NeoDuplicate\NeoGlobal\plugin_slug(), \NeoDuplicate\NeoGlobal\plugin_edition(), \NeoDuplicate\NeoGlobal\plugin_version());
    \NeoDuplicate\NeoGlobal\add_action_hook("shutdown:max", function() {
        \NeoDuplicate\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Uninstall plugin " . \NeoDuplicate\NeoGlobal\plugin_slug() . "-" . \NeoDuplicate\NeoGlobal\plugin_edition());

        clear_plugin_cache(delete_all: true);
        if (\NeoDuplicate\NeoGlobal\fs_file_exists(WP_CONTENT_DIR . "/cache/neo-log.txt")) { \NeoDuplicate\NeoGlobal\fs_unlink(WP_CONTENT_DIR . "/cache/neo-log.txt"); } /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
        \NeoDuplicate\NeoGlobal\flush_all_third_party_caches();

        foreach (array_keys(get_plugins()) as $plugin_file) {
            if (dirname(\NeoDuplicate\NeoGlobal\wp_plugin_dir() . "/" . $plugin_file) === \NeoDuplicate\NeoGlobal\plugin_path_no_symlink_follow()) { continue; }
            if (!str_starts_with($plugin_file, "neo-")) { continue; }
            return;
        }

        \NeoDuplicate\NeoGlobal\delete_all_neo_options();
    });
}

if (!did_action("pre_uninstall_plugin")) {
    register_uninstall_hook(\NeoDuplicate\NeoGlobal\plugin_entry_file_path(), "\\" . __NAMESPACE__ . "\\" . "uninstall");
}

// Clean up plugin cache folder
function clear_plugin_cache($delete_all = false, $force = false) {
    \NeoDuplicate\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Clear plugin cache " . \NeoDuplicate\NeoGlobal\plugin_slug() . "-" . \NeoDuplicate\NeoGlobal\plugin_edition());

    $cache_path = \NeoDuplicate\NeoGlobal\cache_path(only_get_path: true);
    if (count(\NeoDuplicate\NeoGlobal\fs_glob("$cache_path/custom-*")) > 0 && !$force) { return; }
    \NeoDuplicate\NeoGlobal\wait_until_no_synclock_locks_in_subdirs($cache_path, timeout: 5);

    $failed_delete_items = [];
    if ($delete_all) {
        $delete_success = \NeoDuplicate\NeoGlobal\delete_all($cache_path);
        if (!$delete_success) { $failed_delete_items[]= $cache_path; }
    } else {
        $delete_cache_folder_with_exceptions = function () use (&$cache_path, &$failed_delete_items) {
            $exceptions = [
                ".version",
                ".lock*",
                "neo-log--*",
                "neo-tool-passkey",
                "neo-gpt-engine",
                "cache-hash.json",
                "index.php",
            ];
            $items = \NeoDuplicate\NeoGlobal\array_filter_better(array_merge(\NeoDuplicate\NeoGlobal\fs_glob("$cache_path/*", GLOB_MARK), \NeoDuplicate\NeoGlobal\fs_glob("$cache_path/.*", GLOB_MARK)), function ($item) use ($exceptions) { if (in_array(basename($item), [".", ".."])) { return; } foreach ($exceptions as $ex) { if (\NeoDuplicate\NeoGlobal\fs_fnmatch($ex, basename($item))) return false; } return true; });
            foreach ($items as $item) { $delete_success = \NeoDuplicate\NeoGlobal\delete_all($item); if (!$delete_success) { $failed_delete_items[]= $item; } }
        };

        $delete_cache_folder_with_exceptions();
    }
    if (!empty($failed_delete_items)) { \NeoDuplicate\NeoGlobal\global_warn_with_module_name("_global-activate-deactivate-uninstall", "Could not delete cache folder(s) " . implode(", ", $failed_delete_items)); }

    if (did_action("neo_init")) {                               \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoDraw\interface_draw_pagebuilder_elementor_inline_svg_db_clear_20251118')(); }
    else { \NeoDuplicate\NeoGlobal\add_action_hook("neo_init", function () { \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoDraw\interface_draw_pagebuilder_elementor_inline_svg_db_clear_20251118')(); }); }
}
