<?php
namespace NeoRename\NeoRedirect; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_add_neo_rename_redirects_20250914($redirect_entries) {
    if (empty($redirect_entries)) { \NeoRename\NeoGlobal\throw_global_exception("Trying to add empty list of redirects"); }

    $redirects = \NeoRename\NeoGlobal\option__neo_redirect__list();

    $redirects = array_merge([["redirects" => $redirect_entries, "date" => time()]], $redirects);

    $redirects = \NeoRename\NeoGlobal\array_filter_better($redirects, function ($r) use ($redirect_entries) {
        foreach ($redirect_entries as $new_entry) {
            foreach ($r["redirects"] as $existing_entry) {
                if ($existing_entry["source"] === $new_entry["target"]) { return false; }
            }
        }
        return true;
    });

    \NeoRename\NeoGlobal\option__neo_redirect__list($redirects);
}

\NeoRename\NeoGlobal\register_migration("2026-08-06", function () {
    $renamed_options = [
        ["old_name" => "neo_rename_redirect__enabled", "new_name" => "neo_redirect__enabled", "autoload" => true],
        ["old_name" => "neo_rename_redirect__list", "new_name" => "neo_redirect__list", "autoload" => false],
        ["old_name" => "neo_rename_redirect__last_cleanup_timestamp", "new_name" => "neo_redirect__last_cleanup_timestamp", "autoload" => true],
    ];
    foreach ($renamed_options as $renamed_option) {
        $old_value = get_option($renamed_option["old_name"], null);
        $new_value = get_option($renamed_option["new_name"], null);
        if ($new_value === null && $old_value !== null) { update_option($renamed_option["new_name"], $old_value, $renamed_option["autoload"]); }
        delete_option($renamed_option["old_name"]);
    }
});

function resolve_media_redirect($source_rel_path) {
    return null;
}

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    $render_setting_callback = function () {
        ?>
        <neo-setting-neo-rename id="neo-redirect--settings">
            <div slot="left">
                <h3><?php \NeoRename\NeoGlobal\echo_neo__("301 Redirects", "301 Weiterleitungen") ?></h3>
                <p><?php \NeoRename\NeoGlobal\echo_neo__("Automatically redirect old 404 image URLs to enhance SEO.", "Alte 404-Bild-URLs automatisch umleiten, um SEO zu verbessern.") ?></p>
            </div>
            <div slot="right">
                <?php \NeoRename\NeoGlobal\echo_switch_for_option("neo_redirect__enabled", \NeoRename\NeoGlobal\neo__("Redirects", "Weiterleitungen"), only_enabled_if_pro: true, js_function_on_change: "window.neoRedirectOnToggle") ?> 
            </div>
        </neo-setting-neo-rename><?php
    };
    $neo_rename_available = \NeoRename\NeoGlobal\is_module_available("neo-rename");
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_setting_20260326')($neo_rename_available ? "neo-rename--redirect" : "neo-replace--redirect", $render_setting_callback);
    $assets_setting_callback = function () { }
;
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_setting_callback);
});

function get_redirect_expiry_date($creation_date) {
    return $creation_date + (180 * 24 * 60 * 60);
}

\NeoRename\NeoGlobal\add_filter_hook("wp_delete_file", function ($file) {
    if (!str_starts_with($file, \NeoRename\NeoGlobal\uploads_dir() . "/")) { return $file; }
    $deleted_rel_url = str_replace(\NeoRename\NeoGlobal\uploads_dir() . "/", "", $file);
    $redirects = \NeoRename\NeoGlobal\option__neo_redirect__list();
    $redirects = \NeoRename\NeoGlobal\array_filter_better($redirects, fn ($r) => (resolve_media_redirect($r["redirects"][0]["target"]) ?? $r["redirects"][0]["target"]) !== $deleted_rel_url);
    \NeoRename\NeoGlobal\option__neo_redirect__list($redirects);
    return $file;
});
