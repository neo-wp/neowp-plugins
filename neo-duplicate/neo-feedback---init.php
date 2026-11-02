<?php
namespace NeoDuplicate\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDuplicate\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoDuplicate\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoDuplicate\NeoGlobal\site_host());
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoDuplicate\NeoGlobal\plugin_slug());
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoDuplicate\NeoGlobal\plugin_edition());
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoDuplicate\NeoGlobal\plugin_version());
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-duplicate>
            <div slot="left">
                <h3><?php \NeoDuplicate\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoDuplicate\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-duplicate id="neo-feedback--settings-button"><?php \NeoDuplicate\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-duplicate>
            </div>
        </neo-setting-neo-duplicate><?php
    };
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoDuplicate\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
