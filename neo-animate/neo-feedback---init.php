<?php
namespace NeoAnimate\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoAnimate\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoAnimate\NeoGlobal\site_host());
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoAnimate\NeoGlobal\plugin_slug());
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoAnimate\NeoGlobal\plugin_edition());
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoAnimate\NeoGlobal\plugin_version());
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-animate>
            <div slot="left">
                <h3><?php \NeoAnimate\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoAnimate\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-animate id="neo-feedback--settings-button"><?php \NeoAnimate\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-animate>
            </div>
        </neo-setting-neo-animate><?php
    };
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoAnimate\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
