<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Cache busting of plugin assets to ensure that updated JavaScript and CSS files are loaded after a plugin update, including support for JS module imports to prevent outdated libraries from being imported into current code, which could potentially break functionality for the user.
function cachebust_and_get_plugin_url() {
    try {
        \NeoAlt\NeoGlobal\performance_checkpoint("cachebust", is_start: true);

        $cache_bust_dir = \NeoAlt\NeoGlobal\plugin_edition() === "dev" ? \NeoAlt\NeoGlobal\cache_path("cache-bust-dev") : \NeoAlt\NeoGlobal\cache_path("cache-bust");
        $cache_bust_url = \NeoAlt\NeoGlobal\plugin_edition() === "dev" ? \NeoAlt\NeoGlobal\cache_url("cache-bust-dev")  : \NeoAlt\NeoGlobal\cache_url("cache-bust");

        if ($cache_bust_dir === false) { \NeoAlt\NeoGlobal\throw_global_exception("Cache path not writable or could not be created!"); }
        static $cachebusted_assets_folder_name = null;
        if ($cachebusted_assets_folder_name === null) {
            $cachebusted_assets_folder_name = "assets-" . \NeoAlt\NeoGlobal\plugin_version();

            if (\NeoAlt\NeoGlobal\plugin_edition() === "dev") {
                $newest_modified_time = 0;
                foreach (\NeoAlt\NeoGlobal\iterate_all_files(\NeoAlt\NeoGlobal\plugin_path()) as $file_path) {
                    $file_modified_time = \NeoAlt\NeoGlobal\fs_filemtime($file_path);
                    if ($file_modified_time > $newest_modified_time) { $newest_modified_time = $file_modified_time; }
                }
                $cachebusted_assets_folder_name .= "." . \NeoAlt\NeoGlobal\utc_date_string("Y-m-d_H-i-s", $newest_modified_time);
            }
        }

        [$color_theme, $interface_ok] = \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoProColorTheme\interface_get_color_theme_20260106')();
        if (!$interface_ok) { $color_theme = "theme-neo"; }

        $new_cachebusted_assets_dir          = $cache_bust_dir . "/" . $cachebusted_assets_folder_name;
        $new_cachebusted_assets_dir_relative = "."             . "/" . $cachebusted_assets_folder_name;
        $new_cachebusted_assets_dir_with_color_theme_subdir = $new_cachebusted_assets_dir . "/" . "free--" . $color_theme;

        $new_cachebusted_assets_ready_file = $new_cachebusted_assets_dir_with_color_theme_subdir . "/.ready";
        if (!\NeoAlt\NeoGlobal\fs_file_exists($new_cachebusted_assets_ready_file)) {
            \NeoAlt\NeoGlobal\synclock_dir($cache_bust_dir, function () use (&$new_cachebusted_assets_dir, &$new_cachebusted_assets_dir_relative, &$new_cachebusted_assets_dir_with_color_theme_subdir, &$new_cachebusted_assets_ready_file, &$color_theme, &$cache_bust_dir) {
                if (\NeoAlt\NeoGlobal\fs_file_exists($new_cachebusted_assets_ready_file)) { return; }
                if (\NeoAlt\NeoGlobal\fs_is_link($new_cachebusted_assets_dir)) { \NeoAlt\NeoGlobal\fs_unlink($new_cachebusted_assets_dir); }
                if (!\NeoAlt\NeoGlobal\fs_file_exists(\NeoAlt\NeoGlobal\plugin_path())) { return; }
                if (\NeoAlt\NeoGlobal\fs_file_exists($new_cachebusted_assets_dir_with_color_theme_subdir)) { \NeoAlt\NeoGlobal\delete_all($new_cachebusted_assets_dir_with_color_theme_subdir); }
                \NeoAlt\NeoGlobal\copy_all(\NeoAlt\NeoGlobal\plugin_path(), $new_cachebusted_assets_dir_with_color_theme_subdir, function ($path) { return pathinfo($path, PATHINFO_EXTENSION) !== "php"; });

                foreach (\NeoAlt\NeoGlobal\iterate_all_files($new_cachebusted_assets_dir_with_color_theme_subdir) as $file_path) {
                    if (!\NeoAlt\NeoGlobal\preg_match_better("/\.(css|js|svg|html)$/i", $file_path)) { continue; }
                    $file_content = \NeoAlt\NeoGlobal\fs_file_get_contents($file_path);

                    if (str_ends_with($file_path, ".js") && basename($file_path) !== "_global-pro-check-js-variable.js") {
                        $file_content = str_replace("proCheck()", "false", $file_content);
                        $file_content = \NeoAlt\NeoGlobal\preg_replace_better('~^ *import *\{ *proCheck *\} *from *(["\'`])\./' . '_global-pro-check-js-variable\.js' . '\1 *; *$~m', '', $file_content);
                    }

                    \NeoAlt\NeoGlobal\fs_file_put_contents($file_path, \NeoAlt\NeoGlobal\transform_colors(string: $file_content, filename: basename($file_path), theme: $color_theme));
                }
                \NeoAlt\NeoGlobal\fs_file_put_contents($new_cachebusted_assets_ready_file, "ready");

                $symlink_folder_names_referenced_in_custom_website_cache = [];
                $symlink_paths = \NeoAlt\NeoGlobal\array_filter_better(\NeoAlt\NeoGlobal\fs_glob($cache_bust_dir . "/*"), fn ($path) => \NeoAlt\NeoGlobal\fs_is_link($path));
                $custom_website_cache_path = \NeoAlt\NeoGlobal\cache_path() . "/custom-website-cache";
                if (\NeoAlt\NeoGlobal\fs_file_exists($custom_website_cache_path) && !empty($symlink_paths)) {
                    $symlink_folder_name_regex = "/" . implode("|", array_map(function ($part) { return preg_quote(basename($part), "/"); }, $symlink_paths)) . "/";
                    foreach (\NeoAlt\NeoGlobal\iterate_all_files($custom_website_cache_path) as $custom_website_cache_file) {
                        $cache_html_contents = \NeoAlt\NeoGlobal\fs_file_get_contents($custom_website_cache_file);
                        \NeoAlt\NeoGlobal\preg_match_all_better($symlink_folder_name_regex, $cache_html_contents, $matches);
                        $symlink_folder_names_referenced_in_custom_website_cache = array_merge($symlink_folder_names_referenced_in_custom_website_cache, $matches[0]);
                    }
                    $symlink_folder_names_referenced_in_custom_website_cache = \NeoAlt\NeoGlobal\array_unique_better($symlink_folder_names_referenced_in_custom_website_cache);
                }

                usort($symlink_paths, function ($a, $b) { return -strcmp(basename($a), basename($b)); });
                for ($i = 0; $i < count($symlink_paths); $i ++) {
                    $symlink_path = $symlink_paths[$i];

                    try {
                        \NeoAlt\NeoGlobal\fs_unlink($symlink_path);
                        \NeoAlt\NeoGlobal\fs_symlink($new_cachebusted_assets_dir_relative, $symlink_path);
                    } catch (\Throwable $e) { continue; }

                    if (in_array(basename($symlink_path), $symlink_folder_names_referenced_in_custom_website_cache)) { continue; }

                    if ($i < (300)) {
                        if (\NeoAlt\NeoGlobal\preg_match_better("/^.*\.(\d+)-(\d+)-(\d+)_(\d+)-(\d+)-(\d+)/", basename($symlink_path), $matches)) {
                            $year=$matches[1]; $month=$matches[2]; $day=$matches[3]; $hour=$matches[4]; $minute=$matches[5]; $second=$matches[6];
                            $old_cached_assets_folder_timestamp = \NeoAlt\NeoGlobal\timestamp_from_utc_date_string("$year-$month-$day $hour:$minute:$second");
                            if ($old_cached_assets_folder_timestamp > time() + (5) * 60) { $old_cached_assets_folder_timestamp = 0; }
                            if ($old_cached_assets_folder_timestamp >= time() - (2) * 24 * 60 * 60) { continue; }
                        }
                    }

                    try { \NeoAlt\NeoGlobal\fs_unlink($symlink_path); }
                    catch (\Throwable $e) { continue; }
                }

                $non_trash_folders = \NeoAlt\NeoGlobal\array_filter_better(\NeoAlt\NeoGlobal\fs_glob($cache_bust_dir . "/" . "assets-" . "*"), function ($path) { return \NeoAlt\NeoGlobal\fs_is_dir($path) && !\NeoAlt\NeoGlobal\fs_is_link($path) && !str_contains($path, "-trash-"); });
                foreach ($non_trash_folders as $old_cachebusted_asset_dir) {
                    if ($old_cachebusted_asset_dir === $new_cachebusted_assets_dir) { continue; }
                    $old_cachebusted_asset_dir_trash = $old_cachebusted_asset_dir . "-trash-" . \NeoAlt\NeoGlobal\unique_planck_date();
                    \NeoAlt\NeoGlobal\fs_rename($old_cachebusted_asset_dir, $old_cachebusted_asset_dir_trash);
                    try { \NeoAlt\NeoGlobal\fs_symlink($new_cachebusted_assets_dir_relative, $old_cachebusted_asset_dir); }
                    catch (\Throwable $e) { continue; }
                }

                foreach (\NeoAlt\NeoGlobal\fs_glob($cache_bust_dir . "/" . "assets-" . "*-trash-*") as $old_cachebusted_asset_dir) {
                    \NeoAlt\NeoGlobal\delete_all($old_cachebusted_asset_dir);
                }
            });
        }

        return \NeoAlt\NeoGlobal\str_replace_start($cache_bust_dir, $cache_bust_url, $new_cachebusted_assets_dir_with_color_theme_subdir);
    } catch (\Throwable $e) {
        \NeoAlt\NeoGlobal\global_warn_with_module_name("_global-cache-bust", "Could not cachebust assets. Using normal assets folder. Error: " . $e->getMessage());
        return untrailingslashit(plugins_url("", \NeoAlt\NeoGlobal\plugin_entry_file_path()));
    } finally {
        \NeoAlt\NeoGlobal\performance_checkpoint("cachebust", is_end: true);
    }
}
