<?php
namespace NeoDraw\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Create a dedicated cache directory for the neoPlugin in the filesystem location designated by WordPress.
function cache_path($subdir = null, $only_get_path = false) {
    try {
        \NeoDraw\NeoGlobal\performance_checkpoint("cache_path", is_start: true);
        if (str_contains($subdir ?? "", "/")) { throw new \Error("Cache subdir must not contain slashes!"); }

        static $cache_path_cache = [];
        if (isset($cache_path_cache[$subdir ?? "null"]) && !\NeoDraw\NeoGlobal\fs_file_exists($cache_path_cache[$subdir ?? "null"])) { unset($cache_path_cache[$subdir ?? "null"]); }
        if (isset($cache_path_cache[$subdir ?? "null"])) { return $cache_path_cache[$subdir ?? "null"]; }

        if ($subdir === "neo-log") {
            $cache_path = cache_path();
            return \NeoDraw\NeoGlobal\synclock_dir($cache_path, function () use (&$cache_path, &$subdir) {
                $existing_subdir = (\NeoDraw\NeoGlobal\fs_glob($cache_path . "/$subdir--*", GLOB_ONLYDIR) ?: [])[0] ?? null;
                if ($existing_subdir) { return cache_path(basename($existing_subdir)); }
                return cache_path($subdir . "--" . bin2hex(random_bytes(8)));
            });
        }

        $cache_path = WP_CONTENT_DIR . "/cache/" . \NeoDraw\NeoGlobal\get_plugin_cache_folder_name(\NeoDraw\NeoGlobal\plugin_slug(), \NeoDraw\NeoGlobal\plugin_edition()); /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
        if ($only_get_path) { return $cache_path . ($subdir ? ("/" . $subdir) : ""); }
        \NeoDraw\NeoGlobal\suppress_log(true); try {
            \NeoDraw\NeoGlobal\mkdir_better(dirname($cache_path));
            \NeoDraw\NeoGlobal\synclock_dir(dirname($cache_path), function() use ($cache_path) { \NeoDraw\NeoGlobal\mkdir_better($cache_path); });

            return \NeoDraw\NeoGlobal\synclock_dir($cache_path, function() use (&$cache_path_cache, &$cache_path, &$subdir) {
                $cleanup_cache = false;
                $version_file = $cache_path . "/.version";
                if (!\NeoDraw\NeoGlobal\fs_file_exists($version_file) || \NeoDraw\NeoGlobal\plugin_version() !== \NeoDraw\NeoGlobal\fs_file_get_contents($version_file)) {
                    $cleanup_cache = true;
                    \NeoDraw\NeoGlobal\fs_file_put_contents($version_file, \NeoDraw\NeoGlobal\plugin_version());
                }

                if (\NeoDraw\NeoGlobal\is_live_update_version(\NeoDraw\NeoGlobal\plugin_version())) { $cleanup_cache = false; }

                if ($cleanup_cache) {
                    \NeoDraw\NeoGlobal\clear_plugin_cache(delete_all: false);
                }

                $cache_hash_path = $cache_path . "/cache-hash.json";
                $plugin_hash_path = \NeoDraw\NeoGlobal\plugin_path() . "/" . "plugin-hash.txt";
                $is_cache_hash_valid = function () use ($cache_hash_path) { if (\NeoDraw\NeoGlobal\plugin_edition() === "dev") { return true; } $cache_hash_contents = \NeoDraw\NeoGlobal\json_decode_better(\NeoDraw\NeoGlobal\fs_file_get_contents($cache_hash_path) ?: "[]", suppress_error: true); if ($cache_hash_contents === false) { \NeoDraw\NeoGlobal\global_warn_with_module_name("_global-cache", "Broken cache hash JSON"); return false; } return ($cache_hash_contents["cache_hash"] ?? "cache_hash") === ($cache_hash_contents["plugin_hash"] ?? "plugin_hash"); };
                if (\NeoDraw\NeoGlobal\fs_file_exists($plugin_hash_path) && (!\NeoDraw\NeoGlobal\fs_file_exists($cache_hash_path) || \NeoDraw\NeoGlobal\is_cache_file_outdated($cache_hash_path, (7) * 24 * 60 * 60) || !$is_cache_hash_valid())) {
                    $hash_dbg = false;
                    $cache_hash = "";
                    if ($hash_dbg) { $cache_hash_dbg = ""; }
                    $files = iterator_to_array(\NeoDraw\NeoGlobal\iterate_all_files(\NeoDraw\NeoGlobal\plugin_path()));
                    usort($files, function ($a, $b) { return strcmp($a, $b); });
                    foreach ($files as $file) {
                        $file_ending = pathinfo(__FILE__, PATHINFO_EXTENSION);
                        if (!str_ends_with($file, ".$file_ending")) { continue; }
                        if (str_ends_with($file, "/neo-pro--activate-license" . ".$file_ending")) { continue; }
                        $cache_hash .= md5(\NeoDraw\NeoGlobal\fs_file_get_contents($file));
                        if ($hash_dbg) { $cache_hash_dbg .= "=== " . \NeoDraw\NeoGlobal\make_plugin_file_path_relative($file) . " ===\n" . \NeoDraw\NeoGlobal\fs_file_get_contents($file) . "\n\nIn between hash: $cache_hash\n\n\n\n"; }
                    }
                    $cache_hash = md5($cache_hash);
                    \NeoDraw\NeoGlobal\fs_file_put_contents($cache_hash_path, \NeoDraw\NeoGlobal\json_encode_better(["cache_hash" => $cache_hash, "plugin_hash" => \NeoDraw\NeoGlobal\fs_file_get_contents($plugin_hash_path)]));
                    if ($hash_dbg) {
                        $plugin_hash_path_dbg = \NeoDraw\NeoGlobal\plugin_path() . "/" . "plugin-hash-debug.txt";
                        \NeoDraw\NeoGlobal\fs_file_put_contents(str_replace(".json", "-debug-cache-hash.txt",  $cache_hash_path), str_replace(["\r\n", "\n"], ["R\n", "N\n"], $cache_hash_dbg));
                        \NeoDraw\NeoGlobal\fs_file_put_contents(str_replace(".json", "-debug-plugin-hash.txt", $cache_hash_path), str_replace(["\r\n", "\n"], ["R\n", "N\n"], \NeoDraw\NeoGlobal\fs_file_exists($plugin_hash_path_dbg) ? \NeoDraw\NeoGlobal\fs_file_get_contents($plugin_hash_path_dbg) : ""));
                    }
                }

                // Add empty index.php to the cache folder for security (no directory listing allowed)
                if (!\NeoDraw\NeoGlobal\fs_file_exists($cache_path . "/index.php")) { \NeoDraw\NeoGlobal\fs_file_put_contents($cache_path . "/index.php", "<?php\n// Silence is golden.\n"); }
                $cache_path_with_subdir = $cache_path . ($subdir ? ("/" . $subdir) : "");
                \NeoDraw\NeoGlobal\mkdir_better($cache_path_with_subdir);

                $cache_path_cache[$subdir ?? "null"] = $cache_path_with_subdir;

                return $cache_path_with_subdir;
            });
        } finally {
            \NeoDraw\NeoGlobal\suppress_log(false);
        }
    } finally { \NeoDraw\NeoGlobal\performance_checkpoint("cache_path", is_end: true); }
}

function cache_url($subdir = null) {
    $cache_path = cache_path($subdir);
    if (!str_starts_with($cache_path, WP_CONTENT_DIR . "/")) { throw new \Error("Cache path must start with WP_CONTENT_DIR!"); } /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
    return content_url() . substr($cache_path, strlen(WP_CONTENT_DIR)); /* Usage of WP_CONTENT_DIR is OK here because there is no better alternative for the wp-content/cache folder #suppressLinterWporgDirectoryConstantCheck */
}

\NeoDraw\NeoGlobal\add_action_hook("neo_init", function () { cache_path(); });
