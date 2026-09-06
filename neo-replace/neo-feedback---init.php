<?php
namespace NeoReplace\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoReplace\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoReplace\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoReplace\NeoGlobal\site_host());
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoReplace\NeoGlobal\plugin_slug());
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoReplace\NeoGlobal\plugin_edition());
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoReplace\NeoGlobal\plugin_version());
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-replace>
            <div slot="left">
                <h3><?php \NeoReplace\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoReplace\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-replace id="neo-feedback--settings-button"><?php \NeoReplace\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-replace>
            </div>
        </neo-setting-neo-replace><?php
    };
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoReplace\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
