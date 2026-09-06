<?php
namespace NeoAlt\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAlt\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoAlt\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoAlt\NeoGlobal\site_host());
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoAlt\NeoGlobal\plugin_slug());
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoAlt\NeoGlobal\plugin_edition());
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoAlt\NeoGlobal\plugin_version());
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-alt>
            <div slot="left">
                <h3><?php \NeoAlt\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoAlt\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-alt id="neo-feedback--settings-button"><?php \NeoAlt\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-alt>
            </div>
        </neo-setting-neo-alt><?php
    };
    \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoAlt\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
