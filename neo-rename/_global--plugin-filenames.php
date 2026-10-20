<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Centralized naming and routing utility providing deterministic slug/edition/version parsing, stable bundle identifiers, and forward-compatible namespace and endpoint resolution across all neoWP builds.
function get_plugin_slug                                  ($filename_or_path)                       { \NeoRename\NeoGlobal\preg_match_better('/(?P<plugin_slug>.*?)(?:-(?P<edition>full|pro|beta|dev))?\.(?P<version>.*?)\.zip/', basename($filename_or_path), $m); return $m["plugin_slug"] ?? null; }
function get_plugin_edition                               ($filename_or_path)                       { \NeoRename\NeoGlobal\preg_match_better('/(?P<plugin_slug>.*?)(?:-(?P<edition>full|pro|beta|dev))?\.(?P<version>.*?)\.zip/', basename($filename_or_path), $m); return $m["edition"] ?: "wporg"; }
function get_plugin_version                               ($filename_or_path)                       { \NeoRename\NeoGlobal\preg_match_better('/(?P<plugin_slug>.*?)(?:-(?P<edition>full|pro|beta|dev))?\.(?P<version>.*?)\.zip/', basename($filename_or_path), $m); return $m["version"] ?? null; }

function get_plugin_name                                  ($plugin_slug, $edition)                  { $rules = ["Neo" => "neo", "Tool" => "Tool "]; return implode("", array_map(function ($p) use ($rules) { return $rules[ucfirst($p)] ?? ucfirst($p); }, explode("-", $plugin_slug))) . (in_array($edition, ["beta", "pro"]) ? (" " . ucfirst($edition)) : ""); }
function get_plugin_seo_name                              ($plugin_slug, $edition)                  { return str_replace("neo", "neo ", get_plugin_name($plugin_slug, $edition)); }

function get_bundle_slug                                  ($plugin_slug, $edition)                  { return $plugin_slug . ($edition === "wporg" ? "" : "-$edition"); }

function get_plugin_log_id                                ($plugin_slug, $edition, $version)        { return $plugin_slug . "-" . $edition . "." . $version; }

function get_zip_file_name_prefix                         ($plugin_slug, $edition)                  { return get_bundle_slug($plugin_slug, $edition); }
function get_zip_file_name_without_version                ($plugin_slug, $edition)                  { return get_zip_file_name_prefix($plugin_slug, $edition) . ".zip"; }
function get_zip_file_name                                ($plugin_slug, $edition, $version)        { if (!$version) { \NeoRename\NeoGlobal\throw_global_exception("No version given. Use get_zip_file_name_without_version() to get the ZIP file name without a version number."); } return get_zip_file_name_prefix($plugin_slug, $edition) . "." . $version . ".zip"; }
function get_plugin_cache_folder_name                     ($plugin_slug, $edition)                  { return "$plugin_slug-$edition" . cache_server_username_suffix(); }
function get_plugin_folder_name                           ($plugin_slug)                            { return $plugin_slug; }
function get_plugin_entry_file_name                       ($plugin_slug)                            { return $plugin_slug . ".php"; }
function get_plugin_entry_json_file_name                  ($plugin_slug)                            { return str_replace(".php", ".json", get_plugin_entry_file_name($plugin_slug)); }
function get_plugin_entry_path_relative                   ($plugin_slug)                            { return get_plugin_folder_name($plugin_slug) . "/" . get_plugin_entry_file_name($plugin_slug); }
function get_plugin_info_json_file_name                   ($plugin_slug, $edition)                  { return get_bundle_slug($plugin_slug, $edition) . "-info.json"; }
function get_plugin_info_json_file_url                    ($plugin_slug, $edition)                  { return "https://download." . \NeoRename\NeoGlobal\option__neo_wp_com() . "/" . get_plugin_info_json_file_name($plugin_slug, $edition); }
function get_plugin_download_zip_file_url_without_version ($plugin_slug, $edition)                  { return "https://download." . \NeoRename\NeoGlobal\option__neo_wp_com() . "/" . get_zip_file_name_without_version($plugin_slug, $edition); }
function get_plugin_download_zip_file_url                 ($plugin_slug, $edition, $version)        { return "https://download." . \NeoRename\NeoGlobal\option__neo_wp_com() . "/" . (str_contains($version, ".a") ? "alpha/" : "") . get_zip_file_name($plugin_slug, $edition, $version); }
function get_plugin_download_url                          ()                                        { return "https://download." . \NeoRename\NeoGlobal\option__neo_wp_com() . "/"; }
function get_plugin_namespace_prefix                      ($plugin_slug, $edition)                  { return "Neo" . ucfirst(str_replace("-", "", \NeoRename\NeoGlobal\preg_replace_better('/^neo-/i', "", $plugin_slug))) . ($edition === "wporg" ? "" : ucfirst($edition)); }
function get_bundle_id_uppercase_underscore               ($plugin_slug, $edition)                  { return str_replace("-", "_", strtoupper($plugin_slug) . ($edition === "wporg" ? "" : ("_" . strtoupper($edition)))); }
function get_bundle_id_lowercase_dash                     ($plugin_slug, $edition)                  { return $plugin_slug . ($edition === "wporg" ? "" : ("-" . $edition)); }
function get_bundle_id_lowercase_underscore               ($plugin_slug, $edition)                  { return str_replace("-", "_", $plugin_slug . ($edition === "wporg" ? "" : ("_" . $edition))); }
function get_bundle_id_pascalcase                         ($plugin_slug, $edition)                  { return str_replace(" ", "", ucwords(str_replace("-", " ", $plugin_slug))) . ($edition === "wporg" ? "" : ucfirst($edition)); }
function get_bundle_id_camelcase                          ($plugin_slug, $edition)                  { return lcfirst(get_bundle_id_pascalcase($plugin_slug, $edition)); }
function get_plugin_list_and_icons_assets_url             ()                                        { return "https://download." . \NeoRename\NeoGlobal\option__neo_wp_com() . "/_global-plugin-list-and-icons"; }
function get_plugin_list_filename                         ($user_type)                              { return "_plugin-list-$user_type.json"; }
function get_plugin_icon_filename                         ($plugin_slug)                            { return "$plugin_slug-icon.svg"; }
function get_plugin_icon_filename_animated                ($plugin_slug)                            { return "$plugin_slug-icon-animated.svg"; }
function get_neo_pro_advantages_url                       ($edition)                                { if ($edition === "dev") { $edition = "full"; } return "https://download." . \NeoRename\NeoGlobal\option__neo_wp_com() . "/neo-pro-advantages/neo-pro-advantages-$edition.svg"; }
function get_neo_pricing_page_freemius_hint_url           ()                                        { return "https://download." . \NeoRename\NeoGlobal\option__neo_wp_com() . "/neo-pricing-page/neo-pricing-page-neo-freemius-hint.svg"; }
function is_alpha_update_version($version) { return str_contains($version, ".a"); }
function is_live_update_version($version)  { return str_contains($version, ".l"); }
function get_module_name($rel_path) {
    if (str_starts_with($rel_path, "/")) { \NeoRename\NeoGlobal\throw_global_exception("Invalid relative path: $rel_path - cannot start with /"); }
    return \NeoRename\NeoEntrypoint\_neo_entry_function_get_module_name($rel_path);
}

function plugin_version_without_live_timestamp($version = null) { return \NeoRename\NeoGlobal\preg_replace_better('/\.l\d+/', "", $version ?? \NeoRename\NeoGlobal\plugin_version()); }
function plugin_path() { return dirname(\NeoRename\NeoGlobal\plugin_entry_file_path()); }
function wp_plugin_dir() {
    global $wp_plugin_paths; $plugin_entry_file_path = wp_normalize_path(\NeoRename\NeoGlobal\plugin_entry_file_path());
    foreach ($wp_plugin_paths ?? [] as $plugin_path => $real_plugin_path) { if (str_starts_with($plugin_entry_file_path, $real_plugin_path . "/")) { return dirname($plugin_path); } }
    return dirname(dirname($plugin_entry_file_path));
}
function plugin_path_no_symlink_follow() {
    global $wp_plugin_paths; $plugin_entry_file_path = wp_normalize_path(\NeoRename\NeoGlobal\plugin_entry_file_path());
    foreach ($wp_plugin_paths ?? [] as $plugin_path => $real_plugin_path) { if (str_starts_with($plugin_entry_file_path, $real_plugin_path . "/")) { return $plugin_path; } }
    return dirname($plugin_entry_file_path);
}
function plugin_url() {
    if (!function_exists('\NeoRename\NeoGlobal\cachebust_and_get_plugin_url')) { return untrailingslashit(plugins_url("", \NeoRename\NeoGlobal\plugin_entry_file_path())); }
    return \NeoRename\NeoGlobal\cachebust_and_get_plugin_url();
}
function plugin_existing_modules() {
    $this_plugin_info = null; foreach (\NeoRename\NeoEntrypoint\get_neo_active_plugins() as $p) { if ($p["slug"] === \NeoRename\NeoGlobal\plugin_slug() && $p["edition"] === \NeoRename\NeoGlobal\plugin_edition() && $p["version"] === \NeoRename\NeoGlobal\plugin_version()) { $this_plugin_info = $p; break; } }
    if ($this_plugin_info === null) { \NeoRename\NeoGlobal\global_warn_with_module_name("_global--plugin-filenames", "Could not find my own plugin info in the list of active plugins. This should never happen."); }
    return $this_plugin_info["existing_modules"] ?? [];
}
function make_plugin_file_path_relative($abs_path) {
    foreach (\NeoRename\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        $active_plugin_path = untrailingslashit($active_plugin["plugin_path"] ?? "");
        if ($active_plugin_path !== "" && str_starts_with($abs_path, $active_plugin_path . "/")) { return substr($abs_path, strlen($active_plugin_path . "/")); }
    }

    $rel_path = \NeoRename\NeoGlobal\preg_replace_better('#^(?:' . preg_quote(wp_plugin_dir(), '#') . ')/[^/]+/#', "", $abs_path);
    if (str_starts_with($abs_path, "/var/www/neowp/neowp")) { $rel_path = ltrim(str_replace("/var/www/neowp/neowp", "", $abs_path), "/"); }
    if ($rel_path === $abs_path) { \NeoRename\NeoGlobal\throw_global_exception("Invalid absolute path: $abs_path - cannot be converted to relative path within plugin"); }
    return $rel_path;
}
function is_plugin_symlinked() { return \NeoRename\NeoGlobal\plugin_slug() === "neowp" && \NeoRename\NeoGlobal\fs_is_link(plugin_path_no_symlink_follow()); } /*  */
function cache_server_username_suffix() {
    $server_username = null;
    if (function_exists("posix_getuid") && function_exists("posix_getpwuid")) {
        $user_info = @posix_getpwuid(@posix_getuid());
        if (!empty($user_info["name"])) { $server_username = $user_info["name"]; }
    }

    return in_array($server_username, [null, "www-data"]) ? "" : "_" . $server_username;
}

\NeoRename\NeoGlobal\add_filter_hook("plugin_row_meta", function ($plugin_meta, $plugin_file, $plugin_data, $status) {
    if ($plugin_file === plugin_basename(\NeoRename\NeoGlobal\plugin_entry_file_path())) { $plugin_meta[0] .= " " . \NeoRename\NeoGlobal\plugin_edition(); }
    return $plugin_meta;
});
