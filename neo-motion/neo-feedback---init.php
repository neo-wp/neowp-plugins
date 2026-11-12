<?php
namespace NeoMotion\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoMotion\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoMotion\NeoGlobal\site_host());
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoMotion\NeoGlobal\plugin_slug());
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoMotion\NeoGlobal\plugin_edition());
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoMotion\NeoGlobal\plugin_version());
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-motion>
            <div slot="left">
                <h3><?php \NeoMotion\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoMotion\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-motion id="neo-feedback--settings-button"><?php \NeoMotion\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-motion>
            </div>
        </neo-setting-neo-motion><?php
    };
    \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoMotion\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
