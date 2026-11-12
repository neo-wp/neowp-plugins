<?php
namespace NeoDraw\NeoProInstaller; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Enable downloading of Pro content as a ZIP file when the user explicitly requests it via a button
\NeoDraw\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoDraw\NeoGlobal\query_param("page") === \NeoDraw\NeoGlobal\plugin_settings_page_slug() || \NeoDraw\NeoGlobal\query_param("page") === \NeoDraw\NeoGlobal\plugin_settings_page_slug() . "-account")) { return; }

    foreach (\NeoDraw\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (in_array($active_plugin["edition"], ["pro", "beta"])) { return; }
    }

    [$license_key, $interface_ok] = \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoFreemius\interface_freemius_license_secret_key_20260211')();
    if (!$interface_ok || $license_key === null) { return; }

    if (\NeoDraw\NeoGlobal\plugin_edition() === "dev") { return; }

    $is_pro_installer_allowed = !(\NeoDraw\NeoGlobal\plugin_edition() === "wporg");

    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoProInstallerIsProInstallerAllowed", $is_pro_installer_allowed);
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoProInstallerPluginsUrl",            admin_url("plugins.php"));
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoProInstallerNeoMediaProZipUrl",     \NeoDraw\NeoGlobal\add_query_param(\NeoDraw\NeoGlobal\get_plugin_download_zip_file_url_without_version("neo-media", "pro"), "license-key", $license_key));
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoProInstallerSettingsPageUrl",       admin_url("admin.php?page=" . \NeoDraw\NeoGlobal\plugin_settings_page_slug()));
    \NeoDraw\NeoGlobal\enqueue_js("neo-pro-installer.js");
});

if (function_exists('\NeoDraw\NeoGlobal\register_install_neo_media_pro_rest_endpoint')) { \NeoDraw\NeoGlobal\register_install_neo_media_pro_rest_endpoint(); }

\NeoDraw\NeoGlobal\register_rest_endpoint("/wp-json/neo/neo-pro-installer--remove-license-key", "POST", fn () => \NeoDraw\NeoGlobal\current_user_can__neo_pro_installer(), function ($get_param) {
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoFreemius\interface_freemius_delete_account_event_20260211', throw_if_interface_not_ok: true)();
    return "OKAY";
});
