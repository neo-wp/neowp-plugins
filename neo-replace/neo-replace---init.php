<?php
namespace NeoReplace\NeoReplace; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () {
    [$neo_replace_settings_section_url, $interface_ok] = \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-replace"); if (!$interface_ok) { $neo_replace_settings_section_url = ""; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoReplaceSettingsSectionUrl", $neo_replace_settings_section_url);
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-replace", \NeoReplace\NeoGlobal\add_or_update_query_params(admin_url("upload.php"), ["mode" => "list", "neo-library--suppress-redirect" => "true"]) . "#neo-replace--open-tutorial");
});

\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () {
    if (!\NeoReplace\NeoGlobal\is_module_available("neo-rename")) { return; }

    [$neo_rename_settings_url, $interface_ok] = \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-rename"); if (!$interface_ok) { $neo_rename_settings_url = ""; }
    $render_redirect_settings_hint_callback = function () use ($neo_rename_settings_url) {?>
        <neo-setting-neo-replace id="neo-replace--redirect-settings-hint">
            <div slot="left">
                <h3><?php \NeoReplace\NeoGlobal\echo_neo__("301 Redirects", "301 Weiterleitungen") ?></h3>
                <p><?php \NeoReplace\NeoGlobal\echo_neo__("The 301 redirects for neoReplace are managed together with the redirects from neoRename in the neoRename settings.", "Die 301-Weiterleitungen für neoReplace werden gemeinsam mit den Weiterleitungen von neoRename in den neoRename-Einstellungen verwaltet.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-replace href="<?php echo esc_url($neo_rename_settings_url) ?>"><?php \NeoReplace\NeoGlobal\echo_neo__("neoRename settings", "neoRename Einstellungen") ?></neo-button-neo-replace>
            </div>
        </neo-setting-neo-replace><?php
    };
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_add_neo_setting_20260326')("neo-replace--redirect-hint", $render_redirect_settings_hint_callback);
});

\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    if (!\NeoReplace\NeoGlobal\current_user_can__neo_replace()) { return; }
    global $pagenow;
    $screen = get_current_screen();
    if (!($pagenow === "upload.php" || (($screen?->base ?? "") === "post" && ($screen?->post_type ?? "") === "attachment"))) { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoReplaceAllowedMimeTypes", get_allowed_mime_types());
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoReplaceMaxUploadFileSize", wp_max_upload_size());
    if (!(version_compare(get_bloginfo("version"), "7.1-alpha", ">=") && \function_exists("wp_is_client_side_media_processing_enabled") && wp_is_client_side_media_processing_enabled() && wp_script_is("wp-upload-media", "registered") && wp_script_is("wp-media-utils", "registered"))) { return; }
    wp_enqueue_script("wp-upload-media"); wp_enqueue_script("wp-media-utils");
});
