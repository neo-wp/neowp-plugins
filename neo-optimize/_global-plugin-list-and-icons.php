<?php
namespace NeoOptimize\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function update_and_get_plugin_list_and_icons($update_from_server = true) {
    static $download_warning_shown = false;
    $cache_path = \NeoOptimize\NeoGlobal\cache_path("_global-plugin-list-and-icons");
    $cache_url  = \NeoOptimize\NeoGlobal\cache_url ("_global-plugin-list-and-icons");

    return \NeoOptimize\NeoGlobal\synclock_dir($cache_path, timeout: (2) + ((24) * (2) * (5)) + (1), callback: function () use (&$cache_path, &$cache_url, &$update_from_server, &$download_warning_shown) {
        [$user_type, $interface_ok] = \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoDomainSettings\interface_get_user_type_20260412')(); if (!$interface_ok) { $user_type = "user"; }
        if ($update_from_server && function_exists('\NeoOptimize\NeoGlobal\_update_plugin_list_assets_cache_from_server')) { \NeoOptimize\NeoGlobal\_update_plugin_list_assets_cache_from_server($user_type, $cache_path); }

        if (!\NeoOptimize\NeoGlobal\fs_file_exists($cache_path . "/" . \NeoOptimize\NeoGlobal\get_plugin_list_filename($user_type))) {
            if (\NeoOptimize\NeoGlobal\fs_file_exists(\NeoOptimize\NeoGlobal\plugin_path() . "/_global-plugin-list-and-icons")) {
                foreach (\NeoOptimize\NeoGlobal\iterate_all_files(\NeoOptimize\NeoGlobal\plugin_path() . "/_global-plugin-list-and-icons") as $asset_file_path) { \NeoOptimize\NeoGlobal\fs_copy($asset_file_path, $cache_path . "/" . \NeoOptimize\NeoGlobal\str_replace_start(\NeoOptimize\NeoGlobal\plugin_path() . "/_global-plugin-list-and-icons/", "", $asset_file_path)); }
            } else {
                if ($update_from_server && !$download_warning_shown && str_contains(\NeoOptimize\NeoGlobal\site_host(), "neo-wp.com")) {
                    \NeoOptimize\NeoGlobal\admin_warn("neoManager assets could not be downloaded. Download server down?");
                    $download_warning_shown = true;
                }
                return [[], []];
            }
        }

        foreach (["theme-neo", "theme-pro"] as $color_theme) {
            if ($color_theme === "theme-pro") { continue; }
            foreach (\NeoOptimize\NeoGlobal\iterate_all_files($cache_path) as $asset_file_path) {
                if (!str_ends_with($asset_file_path, ".svg")) { continue; }
                if (str_contains(basename($asset_file_path), "theme-neo") || str_contains(basename($asset_file_path), "theme-pro")) { continue; }
                $themed_filename = str_replace(".svg", "-color-$color_theme.svg", basename($asset_file_path));
                if (\NeoOptimize\NeoGlobal\fs_file_exists($cache_path . "/" . $themed_filename)) { continue; }
                \NeoOptimize\NeoGlobal\fs_file_put_contents(str_replace(".svg", "-color-$color_theme.svg", $asset_file_path), \NeoOptimize\NeoGlobal\transform_colors(\NeoOptimize\NeoGlobal\fs_file_get_contents($asset_file_path), filename: basename($asset_file_path), theme: $color_theme));
            }
        }
        $plugin_list = \NeoOptimize\NeoGlobal\fs_read_json_file($cache_path . "/" . \NeoOptimize\NeoGlobal\get_plugin_list_filename($user_type));

        $plugin_icon_list = [];
        foreach ($plugin_list as $plugin_data) {
            $plugin_icon_list[$plugin_data["plugin-slug"]] = [];
            foreach (["theme-neo", "theme-pro"] as $color_theme) {
                if ($color_theme === "theme-pro") { continue; }
                $plugin_icon_list[$plugin_data["plugin-slug"]][$color_theme]["icon"         ] = $cache_url . "/" . str_replace(".svg", "-color-$color_theme.svg", \NeoOptimize\NeoGlobal\get_plugin_icon_filename($plugin_data["plugin-slug"]));
                $plugin_icon_list[$plugin_data["plugin-slug"]][$color_theme]["icon-animated"] = $cache_url . "/" . str_replace(".svg", "-color-$color_theme.svg", \NeoOptimize\NeoGlobal\get_plugin_icon_filename_animated($plugin_data["plugin-slug"]));
            }
        }
        return [$plugin_list, $plugin_icon_list];
    });
}
