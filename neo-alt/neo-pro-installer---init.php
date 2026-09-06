<?php
namespace NeoAlt\NeoProInstaller; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Enable downloading of Pro content as a ZIP file when the user explicitly requests it via a button
\NeoAlt\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoAlt\NeoGlobal\query_param("page") === \NeoAlt\NeoGlobal\plugin_settings_page_slug() || \NeoAlt\NeoGlobal\query_param("page") === \NeoAlt\NeoGlobal\plugin_settings_page_slug() . "-account")) { return; }

    foreach (\NeoAlt\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (in_array($active_plugin["edition"], ["pro", "beta"])) { return; }
    }

    [$license_key, $interface_ok] = \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoFreemius\interface_freemius_license_secret_key_20260211')();
    if (!$interface_ok || $license_key === null) { return; }

    if (\NeoAlt\NeoGlobal\plugin_edition() === "dev") { return; }

    $is_pro_installer_allowed = !(\NeoAlt\NeoGlobal\plugin_edition() === "wporg");

    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoProInstallerIsProInstallerAllowed", $is_pro_installer_allowed);
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoProInstallerPluginsUrl",            admin_url("plugins.php"));
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoProInstallerNeoMediaProZipUrl",     \NeoAlt\NeoGlobal\add_query_param(\NeoAlt\NeoGlobal\get_plugin_download_zip_file_url_without_version("neo-media", "pro"), "license-key", $license_key));
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoProInstallerSettingsPageUrl",       admin_url("admin.php?page=" . \NeoAlt\NeoGlobal\plugin_settings_page_slug()));
    \NeoAlt\NeoGlobal\enqueue_js("neo-pro-installer.js");
});

if (function_exists('\NeoAlt\NeoGlobal\register_install_neo_media_pro_rest_endpoint')) { \NeoAlt\NeoGlobal\register_install_neo_media_pro_rest_endpoint(); }

\NeoAlt\NeoGlobal\register_rest_endpoint("/wp-json/neo/neo-pro-installer--remove-license-key", "POST", fn () => \NeoAlt\NeoGlobal\current_user_can__neo_pro_installer(), function ($get_param) {
    \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoFreemius\interface_freemius_delete_account_event_20260211', throw_if_interface_not_ok: true)();
    return "OKAY";
});
