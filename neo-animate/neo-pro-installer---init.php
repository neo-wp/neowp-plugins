<?php
namespace NeoAnimate\NeoProInstaller; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Enable downloading of Pro content as a ZIP file when the user explicitly requests it via a button
\NeoAnimate\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoAnimate\NeoGlobal\query_param("page") === \NeoAnimate\NeoGlobal\plugin_settings_page_slug() || \NeoAnimate\NeoGlobal\query_param("page") === \NeoAnimate\NeoGlobal\plugin_settings_page_slug() . "-account")) { return; }

    foreach (\NeoAnimate\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (in_array($active_plugin["edition"], ["pro", "beta"])) { return; }
    }

    [$license_key, $interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoFreemius\interface_freemius_license_secret_key_20260211')();
    if (!$interface_ok || $license_key === null) { return; }

    if (\NeoAnimate\NeoGlobal\plugin_edition() === "dev") { return; }

    $is_pro_installer_allowed = !(\NeoAnimate\NeoGlobal\plugin_edition() === "wporg");

    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoProInstallerIsProInstallerAllowed", $is_pro_installer_allowed);
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoProInstallerPluginsUrl",            admin_url("plugins.php"));
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoProInstallerNeoMediaProZipUrl",     \NeoAnimate\NeoGlobal\add_query_param(\NeoAnimate\NeoGlobal\get_plugin_download_zip_file_url_without_version("neo-media", "pro"), "license-key", $license_key));
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoProInstallerSettingsPageUrl",       admin_url("admin.php?page=" . \NeoAnimate\NeoGlobal\plugin_settings_page_slug()));
    \NeoAnimate\NeoGlobal\enqueue_js("neo-pro-installer.js");
});

if (function_exists('\NeoAnimate\NeoGlobal\register_install_neo_media_pro_rest_endpoint')) { \NeoAnimate\NeoGlobal\register_install_neo_media_pro_rest_endpoint(); }

\NeoAnimate\NeoGlobal\register_rest_endpoint("/wp-json/neo/neo-pro-installer--remove-license-key", "POST", fn () => \NeoAnimate\NeoGlobal\current_user_can__neo_pro_installer(), function ($get_param) {
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoFreemius\interface_freemius_delete_account_event_20260211', throw_if_interface_not_ok: true)();
    return "OKAY";
});
