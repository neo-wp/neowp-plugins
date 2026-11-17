<?php
namespace NeoReplace\NeoProInstaller; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Enable downloading of Pro content as a ZIP file when the user explicitly requests it via a button
\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoReplace\NeoGlobal\query_param("page") === \NeoReplace\NeoGlobal\plugin_settings_page_slug() || \NeoReplace\NeoGlobal\query_param("page") === \NeoReplace\NeoGlobal\plugin_settings_page_slug() . "-account")) { return; }

    foreach (\NeoReplace\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (in_array($active_plugin["edition"], ["pro", "beta"])) { return; }
    }

    [$license_key, $interface_ok] = \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoFreemius\interface_freemius_license_secret_key_20260211')();
    if (!$interface_ok || $license_key === null) { return; }

    if (\NeoReplace\NeoGlobal\plugin_edition() === "dev") { return; }

    $is_pro_installer_allowed = !(\NeoReplace\NeoGlobal\plugin_edition() === "wporg");

    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoProInstallerIsProInstallerAllowed", $is_pro_installer_allowed);
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoProInstallerPluginsUrl",            admin_url("plugins.php"));
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoProInstallerNeoMediaProZipUrl",     \NeoReplace\NeoGlobal\add_query_param(\NeoReplace\NeoGlobal\get_plugin_download_zip_file_url_without_version("neo-media", "pro"), "license-key", $license_key));
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoProInstallerSettingsPageUrl",       admin_url("admin.php?page=" . \NeoReplace\NeoGlobal\plugin_settings_page_slug()));
    \NeoReplace\NeoGlobal\enqueue_js("neo-pro-installer.js");
});

if (function_exists('\NeoReplace\NeoGlobal\register_install_neo_media_pro_rest_endpoint')) { \NeoReplace\NeoGlobal\register_install_neo_media_pro_rest_endpoint(); }

\NeoReplace\NeoGlobal\register_rest_endpoint("/wp-json/neo/neo-pro-installer--remove-license-key", "POST", fn () => \NeoReplace\NeoGlobal\current_user_can__neo_pro_installer(), function ($get_param) {
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoFreemius\interface_freemius_delete_account_event_20260211', throw_if_interface_not_ok: true)();
    return "OKAY";
});
