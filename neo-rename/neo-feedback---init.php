<?php
namespace NeoRename\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoRename\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoRename\NeoGlobal\site_host());
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoRename\NeoGlobal\plugin_slug());
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoRename\NeoGlobal\plugin_edition());
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoRename\NeoGlobal\plugin_version());
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-rename>
            <div slot="left">
                <h3><?php \NeoRename\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoRename\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-rename id="neo-feedback--settings-button"><?php \NeoRename\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-rename>
            </div>
        </neo-setting-neo-rename><?php
    };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoRename\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
