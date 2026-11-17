<?php
namespace NeoMotion\NeoProInstaller; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Enable downloading of Pro content as a ZIP file when the user explicitly requests it via a button
\NeoMotion\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoMotion\NeoGlobal\query_param("page") === \NeoMotion\NeoGlobal\plugin_settings_page_slug() || \NeoMotion\NeoGlobal\query_param("page") === \NeoMotion\NeoGlobal\plugin_settings_page_slug() . "-account")) { return; }

    foreach (\NeoMotion\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (in_array($active_plugin["edition"], ["pro", "beta"])) { return; }
    }

    [$license_key, $interface_ok] = \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoFreemius\interface_freemius_license_secret_key_20260211')();
    if (!$interface_ok || $license_key === null) { return; }

    if (\NeoMotion\NeoGlobal\plugin_edition() === "dev") { return; }

    $is_pro_installer_allowed = !(\NeoMotion\NeoGlobal\plugin_edition() === "wporg");

    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoProInstallerIsProInstallerAllowed", $is_pro_installer_allowed);
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoProInstallerPluginsUrl",            admin_url("plugins.php"));
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoProInstallerNeoMediaProZipUrl",     \NeoMotion\NeoGlobal\add_query_param(\NeoMotion\NeoGlobal\get_plugin_download_zip_file_url_without_version("neo-media", "pro"), "license-key", $license_key));
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoProInstallerSettingsPageUrl",       admin_url("admin.php?page=" . \NeoMotion\NeoGlobal\plugin_settings_page_slug()));
    \NeoMotion\NeoGlobal\enqueue_js("neo-pro-installer.js");
});

if (function_exists('\NeoMotion\NeoGlobal\register_install_neo_media_pro_rest_endpoint')) { \NeoMotion\NeoGlobal\register_install_neo_media_pro_rest_endpoint(); }

\NeoMotion\NeoGlobal\register_rest_endpoint("/wp-json/neo/neo-pro-installer--remove-license-key", "POST", fn () => \NeoMotion\NeoGlobal\current_user_can__neo_pro_installer(), function ($get_param) {
    \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoFreemius\interface_freemius_delete_account_event_20260211', throw_if_interface_not_ok: true)();
    return "OKAY";
});
