<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function update_and_get_plugin_list_and_icons($update_from_server = true) {
    static $download_warning_shown = false;
    $cache_path = \NeoReplace\NeoGlobal\cache_path("_global-plugin-list-and-icons");
    $cache_url  = \NeoReplace\NeoGlobal\cache_url ("_global-plugin-list-and-icons");

    return \NeoReplace\NeoGlobal\synclock_dir($cache_path, timeout: (2) + ((24) * (2) * (5)) + (1), callback: function () use (&$cache_path, &$cache_url, &$update_from_server, &$download_warning_shown) {
        [$user_type, $interface_ok] = \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoDomainSettings\interface_get_user_type_20260412')(); if (!$interface_ok) { $user_type = "user"; }
        if ($update_from_server && function_exists('\NeoReplace\NeoGlobal\_update_plugin_list_assets_cache_from_server')) { \NeoReplace\NeoGlobal\_update_plugin_list_assets_cache_from_server($user_type, $cache_path); }

        if (!\NeoReplace\NeoGlobal\fs_file_exists($cache_path . "/" . \NeoReplace\NeoGlobal\get_plugin_list_filename($user_type))) {
            if (\NeoReplace\NeoGlobal\fs_file_exists(\NeoReplace\NeoGlobal\plugin_path() . "/_global-plugin-list-and-icons")) {
                foreach (\NeoReplace\NeoGlobal\iterate_all_files(\NeoReplace\NeoGlobal\plugin_path() . "/_global-plugin-list-and-icons") as $asset_file_path) { \NeoReplace\NeoGlobal\fs_copy($asset_file_path, $cache_path . "/" . \NeoReplace\NeoGlobal\str_replace_start(\NeoReplace\NeoGlobal\plugin_path() . "/_global-plugin-list-and-icons/", "", $asset_file_path)); }
            } else {
                if ($update_from_server && !$download_warning_shown && str_contains(\NeoReplace\NeoGlobal\site_host(), "neo-wp.com")) {
                    \NeoReplace\NeoGlobal\admin_warn("neoManager assets could not be downloaded. Download server down?");
                    $download_warning_shown = true;
                }
                return [[], []];
            }
        }

        foreach (["theme-neo", "theme-pro"] as $color_theme) {
            if ($color_theme === "theme-pro") { continue; }
            foreach (\NeoReplace\NeoGlobal\iterate_all_files($cache_path) as $asset_file_path) {
                if (!str_ends_with($asset_file_path, ".svg")) { continue; }
                if (str_contains(basename($asset_file_path), "theme-neo") || str_contains(basename($asset_file_path), "theme-pro")) { continue; }
                $themed_filename = str_replace(".svg", "-color-$color_theme.svg", basename($asset_file_path));
                if (\NeoReplace\NeoGlobal\fs_file_exists($cache_path . "/" . $themed_filename)) { continue; }
                \NeoReplace\NeoGlobal\fs_file_put_contents(str_replace(".svg", "-color-$color_theme.svg", $asset_file_path), \NeoReplace\NeoGlobal\transform_colors(\NeoReplace\NeoGlobal\fs_file_get_contents($asset_file_path), filename: basename($asset_file_path), theme: $color_theme));
            }
        }
        $plugin_list = \NeoReplace\NeoGlobal\fs_read_json_file($cache_path . "/" . \NeoReplace\NeoGlobal\get_plugin_list_filename($user_type));

        $plugin_icon_list = [];
        foreach ($plugin_list as $plugin_data) {
            $plugin_icon_list[$plugin_data["plugin-slug"]] = [];
            foreach (["theme-neo", "theme-pro"] as $color_theme) {
                if ($color_theme === "theme-pro") { continue; }
                $plugin_icon_list[$plugin_data["plugin-slug"]][$color_theme]["icon"         ] = $cache_url . "/" . str_replace(".svg", "-color-$color_theme.svg", \NeoReplace\NeoGlobal\get_plugin_icon_filename($plugin_data["plugin-slug"]));
                $plugin_icon_list[$plugin_data["plugin-slug"]][$color_theme]["icon-animated"] = $cache_url . "/" . str_replace(".svg", "-color-$color_theme.svg", \NeoReplace\NeoGlobal\get_plugin_icon_filename_animated($plugin_data["plugin-slug"]));
            }
        }
        return [$plugin_list, $plugin_icon_list];
    });
}
