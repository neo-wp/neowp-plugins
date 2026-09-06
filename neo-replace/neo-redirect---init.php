<?php
namespace NeoReplace\NeoRedirect; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_add_neo_rename_redirects_20250914($redirect_entries) {
    if (empty($redirect_entries)) { \NeoReplace\NeoGlobal\throw_global_exception("Trying to add empty list of redirects"); }

    $redirects = \NeoReplace\NeoGlobal\option__neo_redirect__list();

    $redirects = array_merge([["redirects" => $redirect_entries, "date" => time()]], $redirects);

    $redirects = \NeoReplace\NeoGlobal\array_filter_better($redirects, function ($r) use ($redirect_entries) {
        foreach ($redirect_entries as $new_entry) {
            foreach ($r["redirects"] as $existing_entry) {
                if ($existing_entry["source"] === $new_entry["target"]) { return false; }
            }
        }
        return true;
    });

    \NeoReplace\NeoGlobal\option__neo_redirect__list($redirects);
}

\NeoReplace\NeoGlobal\register_migration("2026-08-06", function () {
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

\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () {
    $render_setting_callback = function () {
        ?>
        <neo-setting-neo-replace id="neo-redirect--settings">
            <div slot="left">
                <h3><?php \NeoReplace\NeoGlobal\echo_neo__("301 Redirects", "301 Weiterleitungen") ?></h3>
                <p><?php \NeoReplace\NeoGlobal\echo_neo__("Automatically redirect old 404 image URLs to enhance SEO.", "Alte 404-Bild-URLs automatisch umleiten, um SEO zu verbessern.") ?></p>
            </div>
            <div slot="right">
                <?php \NeoReplace\NeoGlobal\echo_switch_for_option("neo_redirect__enabled", \NeoReplace\NeoGlobal\neo__("Redirects", "Weiterleitungen"), only_enabled_if_pro: true, js_function_on_change: "window.neoRedirectOnToggle") ?> 
            </div>
        </neo-setting-neo-replace><?php
    };
    $neo_rename_available = \NeoReplace\NeoGlobal\is_module_available("neo-rename");
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_add_neo_setting_20260326')($neo_rename_available ? "neo-rename--redirect" : "neo-replace--redirect", $render_setting_callback);
    $assets_setting_callback = function () { }
;
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_setting_callback);
});

function get_redirect_expiry_date($creation_date) {
    return $creation_date + (180 * 24 * 60 * 60);
}

\NeoReplace\NeoGlobal\add_filter_hook("wp_delete_file", function ($file) {
    if (!str_starts_with($file, \NeoReplace\NeoGlobal\uploads_dir() . "/")) { return $file; }
    $deleted_rel_url = str_replace(\NeoReplace\NeoGlobal\uploads_dir() . "/", "", $file);
    $redirects = \NeoReplace\NeoGlobal\option__neo_redirect__list();
    $redirects = \NeoReplace\NeoGlobal\array_filter_better($redirects, fn ($r) => (resolve_media_redirect($r["redirects"][0]["target"]) ?? $r["redirects"][0]["target"]) !== $deleted_rel_url);
    \NeoReplace\NeoGlobal\option__neo_redirect__list($redirects);
    return $file;
});
