<?php
namespace NeoDraw\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoDraw\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoDraw\NeoGlobal\site_host());
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoDraw\NeoGlobal\plugin_slug());
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoDraw\NeoGlobal\plugin_edition());
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoDraw\NeoGlobal\plugin_version());
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-draw>
            <div slot="left">
                <h3><?php \NeoDraw\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoDraw\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-draw id="neo-feedback--settings-button"><?php \NeoDraw\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-draw>
            </div>
        </neo-setting-neo-draw><?php
    };
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoDraw\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
