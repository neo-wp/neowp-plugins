<?php
namespace NeoDuplicate\NeoProInstaller; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Enable downloading of Pro content as a ZIP file when the user explicitly requests it via a button
\NeoDuplicate\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoDuplicate\NeoGlobal\query_param("page") === \NeoDuplicate\NeoGlobal\plugin_settings_page_slug() || \NeoDuplicate\NeoGlobal\query_param("page") === \NeoDuplicate\NeoGlobal\plugin_settings_page_slug() . "-account")) { return; }

    foreach (\NeoDuplicate\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (in_array($active_plugin["edition"], ["pro", "beta"])) { return; }
    }

    [$license_key, $interface_ok] = \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoFreemius\interface_freemius_license_secret_key_20260211')();
    if (!$interface_ok || $license_key === null) { return; }

    if (\NeoDuplicate\NeoGlobal\plugin_edition() === "dev") { return; }

    $is_pro_installer_allowed = !(\NeoDuplicate\NeoGlobal\plugin_edition() === "wporg");

    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoProInstallerIsProInstallerAllowed", $is_pro_installer_allowed);
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoProInstallerPluginsUrl",            admin_url("plugins.php"));
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoProInstallerNeoMediaProZipUrl",     \NeoDuplicate\NeoGlobal\add_query_param(\NeoDuplicate\NeoGlobal\get_plugin_download_zip_file_url_without_version("neo-media", "pro"), "license-key", $license_key));
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoProInstallerSettingsPageUrl",       admin_url("admin.php?page=" . \NeoDuplicate\NeoGlobal\plugin_settings_page_slug()));
    \NeoDuplicate\NeoGlobal\enqueue_js("neo-pro-installer.js");
});

if (function_exists('\NeoDuplicate\NeoGlobal\register_install_neo_media_pro_rest_endpoint')) { \NeoDuplicate\NeoGlobal\register_install_neo_media_pro_rest_endpoint(); }

\NeoDuplicate\NeoGlobal\register_rest_endpoint("/wp-json/neo/neo-pro-installer--remove-license-key", "POST", fn () => \NeoDuplicate\NeoGlobal\current_user_can__neo_pro_installer(), function ($get_param) {
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoFreemius\interface_freemius_delete_account_event_20260211', throw_if_interface_not_ok: true)();
    return "OKAY";
});
