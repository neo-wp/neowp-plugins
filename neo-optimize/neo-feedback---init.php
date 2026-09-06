<?php
namespace NeoOptimize\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoOptimize\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoOptimize\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoOptimize\NeoGlobal\site_host());
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoOptimize\NeoGlobal\plugin_slug());
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoOptimize\NeoGlobal\plugin_edition());
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoOptimize\NeoGlobal\plugin_version());
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-optimize>
            <div slot="left">
                <h3><?php \NeoOptimize\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoOptimize\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-optimize id="neo-feedback--settings-button"><?php \NeoOptimize\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-optimize>
            </div>
        </neo-setting-neo-optimize><?php
    };
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoOptimize\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
