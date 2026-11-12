<?php
namespace NeoLibrary\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Carefully implemented activation, deactivation and uninstall hooks with late cache cleanup and selective deletion of neo_ options.
// Plugin activation
register_activation_hook(\NeoLibrary\NeoGlobal\plugin_entry_file_path(), function () {
    \NeoLibrary\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Activate plugin " . \NeoLibrary\NeoGlobal\plugin_slug() . "-" . \NeoLibrary\NeoGlobal\plugin_edition());
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoFreemius\interface_freemius_activate_hook_20260210')(\NeoLibrary\NeoGlobal\plugin_slug(), \NeoLibrary\NeoGlobal\plugin_edition(), \NeoLibrary\NeoGlobal\plugin_version());
});

// Plugin deactivation
register_deactivation_hook(\NeoLibrary\NeoGlobal\plugin_entry_file_path(), function () {
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoFreemius\interface_freemius_deactivate_hook_20260210')(\NeoLibrary\NeoGlobal\plugin_slug(), \NeoLibrary\NeoGlobal\plugin_edition(), \NeoLibrary\NeoGlobal\plugin_version());
    \NeoLibrary\NeoGlobal\add_action_hook("shutdown:max", function() {
        \NeoLibrary\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Deactivate plugin " . \NeoLibrary\NeoGlobal\plugin_slug() . "-" . \NeoLibrary\NeoGlobal\plugin_edition());
        clear_plugin_cache(delete_all: false);
        \NeoLibrary\NeoGlobal\flush_all_third_party_caches();
    });
});

// Plugin update
\NeoLibrary\NeoGlobal\add_filter_hook("upgrader_post_install", function ($response, $hook_extra, $result) {
    if (!is_array($result) || !isset($result["destination_name"])) { return $response; }
    if (!($result["destination_name"] === \NeoLibrary\NeoGlobal\get_bundle_slug(\NeoLibrary\NeoGlobal\plugin_slug(), \NeoLibrary\NeoGlobal\plugin_edition()))) { return $response; }
    \NeoLibrary\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Clear cache because of plugin update " . \NeoLibrary\NeoGlobal\plugin_slug() . "-" . \NeoLibrary\NeoGlobal\plugin_edition());
    clear_plugin_cache(delete_all: false);
    \NeoLibrary\NeoGlobal\flush_all_third_party_caches();
    return $response;
});

// Plugin uninstall
function uninstall() {
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoFreemius\interface_freemius_uninstall_hook_20260210')(\NeoLibrary\NeoGlobal\plugin_slug(), \NeoLibrary\NeoGlobal\plugin_edition(), \NeoLibrary\NeoGlobal\plugin_version());
    \NeoLibrary\NeoGlobal\add_action_hook("shutdown:max", function() {
        \NeoLibrary\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Uninstall plugin " . \NeoLibrary\NeoGlobal\plugin_slug() . "-" . \NeoLibrary\NeoGlobal\plugin_edition());

        clear_plugin_cache(delete_all: true);
        if (\NeoLibrary\NeoGlobal\fs_file_exists(WP_CONTENT_DIR . "/cache/neo-log.txt")) { \NeoLibrary\NeoGlobal\fs_unlink(WP_CONTENT_DIR . "/cache/neo-log.txt"); } /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
        \NeoLibrary\NeoGlobal\flush_all_third_party_caches();

        foreach (array_keys(get_plugins()) as $plugin_file) {
            if (dirname(\NeoLibrary\NeoGlobal\wp_plugin_dir() . "/" . $plugin_file) === \NeoLibrary\NeoGlobal\plugin_path_no_symlink_follow()) { continue; }
            if (!str_starts_with($plugin_file, "neo-")) { continue; }
            return;
        }

        \NeoLibrary\NeoGlobal\delete_all_neo_options();
    });
}

if (!did_action("pre_uninstall_plugin")) {
    register_uninstall_hook(\NeoLibrary\NeoGlobal\plugin_entry_file_path(), "\\" . __NAMESPACE__ . "\\" . "uninstall");
}

// Clean up plugin cache folder
function clear_plugin_cache($delete_all = false, $force = false) {
    \NeoLibrary\NeoGlobal\global_log_with_module_name("_global-activate-deactivate-uninstall", "Clear plugin cache " . \NeoLibrary\NeoGlobal\plugin_slug() . "-" . \NeoLibrary\NeoGlobal\plugin_edition());

    $cache_path = \NeoLibrary\NeoGlobal\cache_path(only_get_path: true);
    if (count(\NeoLibrary\NeoGlobal\fs_glob("$cache_path/custom-*")) > 0 && !$force) { return; }
    \NeoLibrary\NeoGlobal\wait_until_no_synclock_locks_in_subdirs($cache_path, timeout: 5);

    $failed_delete_items = [];
    if ($delete_all) {
        $delete_success = \NeoLibrary\NeoGlobal\delete_all($cache_path);
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
            $items = \NeoLibrary\NeoGlobal\array_filter_better(array_merge(\NeoLibrary\NeoGlobal\fs_glob("$cache_path/*", GLOB_MARK), \NeoLibrary\NeoGlobal\fs_glob("$cache_path/.*", GLOB_MARK)), function ($item) use ($exceptions) { if (in_array(basename($item), [".", ".."])) { return; } foreach ($exceptions as $ex) { if (\NeoLibrary\NeoGlobal\fs_fnmatch($ex, basename($item))) return false; } return true; });
            foreach ($items as $item) { $delete_success = \NeoLibrary\NeoGlobal\delete_all($item); if (!$delete_success) { $failed_delete_items[]= $item; } }
        };

        $delete_cache_folder_with_exceptions();
    }
    if (!empty($failed_delete_items)) { \NeoLibrary\NeoGlobal\global_warn_with_module_name("_global-activate-deactivate-uninstall", "Could not delete cache folder(s) " . implode(", ", $failed_delete_items)); }

    if (did_action("neo_init")) {                               \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoDraw\interface_draw_pagebuilder_elementor_inline_svg_db_clear_20251118')(); }
    else { \NeoLibrary\NeoGlobal\add_action_hook("neo_init", function () { \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoDraw\interface_draw_pagebuilder_elementor_inline_svg_db_clear_20251118')(); }); }
}
