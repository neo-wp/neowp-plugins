<?php
namespace NeoLibrary\NeoProInstaller; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Enable downloading of Pro content as a ZIP file when the user explicitly requests it via a button
\NeoLibrary\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoLibrary\NeoGlobal\query_param("page") === \NeoLibrary\NeoGlobal\plugin_settings_page_slug() || \NeoLibrary\NeoGlobal\query_param("page") === \NeoLibrary\NeoGlobal\plugin_settings_page_slug() . "-account")) { return; }

    foreach (\NeoLibrary\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (in_array($active_plugin["edition"], ["pro", "beta"])) { return; }
    }

    [$license_key, $interface_ok] = \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoFreemius\interface_freemius_license_secret_key_20260211')();
    if (!$interface_ok || $license_key === null) { return; }

    if (\NeoLibrary\NeoGlobal\plugin_edition() === "dev") { return; }

    $is_pro_installer_allowed = !(\NeoLibrary\NeoGlobal\plugin_edition() === "wporg");

    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoProInstallerIsProInstallerAllowed", $is_pro_installer_allowed);
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoProInstallerPluginsUrl",            admin_url("plugins.php"));
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoProInstallerNeoMediaProZipUrl",     \NeoLibrary\NeoGlobal\add_query_param(\NeoLibrary\NeoGlobal\get_plugin_download_zip_file_url_without_version("neo-media", "pro"), "license-key", $license_key));
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoProInstallerSettingsPageUrl",       admin_url("admin.php?page=" . \NeoLibrary\NeoGlobal\plugin_settings_page_slug()));
    \NeoLibrary\NeoGlobal\enqueue_js("neo-pro-installer.js");
});

if (function_exists('\NeoLibrary\NeoGlobal\register_install_neo_media_pro_rest_endpoint')) { \NeoLibrary\NeoGlobal\register_install_neo_media_pro_rest_endpoint(); }

\NeoLibrary\NeoGlobal\register_rest_endpoint("/wp-json/neo/neo-pro-installer--remove-license-key", "POST", fn () => \NeoLibrary\NeoGlobal\current_user_can__neo_pro_installer(), function ($get_param) {
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoFreemius\interface_freemius_delete_account_event_20260211', throw_if_interface_not_ok: true)();
    return "OKAY";
});
