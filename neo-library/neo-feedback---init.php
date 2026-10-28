<?php
namespace NeoLibrary\NeoFeedback; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoLibrary\NeoGlobal\add_action_hook("neo_init:11", function () {
    $current_user_email = wp_get_current_user()->user_email;
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEndpointUrl", "https://download." . \NeoLibrary\NeoGlobal\option__neo_wp_com() . "/feedback");
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackEmail", $current_user_email);
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackDomain", \NeoLibrary\NeoGlobal\site_host());
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginSlug", \NeoLibrary\NeoGlobal\plugin_slug());
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginEdition", \NeoLibrary\NeoGlobal\plugin_edition());
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPluginVersion", \NeoLibrary\NeoGlobal\plugin_version());
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackWordPressVersion", get_bloginfo("version"));
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackPhpVersion", PHP_VERSION);
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoFeedbackLocale", determine_locale());

    $settings_render_callback = function () {?>
        <neo-setting-neo-library>
            <div slot="left">
                <h3><?php \NeoLibrary\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></h3>
                <p><?php \NeoLibrary\NeoGlobal\echo_neo__("Share an idea, a wish, or anything we can improve. Your feedback goes directly to the neoWP team.", "Teile eine Idee, einen Wunsch oder etwas, das wir verbessern können. Dein Feedback geht direkt an das neoWP-Team.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-library id="neo-feedback--settings-button"><?php \NeoLibrary\NeoGlobal\echo_neo__("Send feedback", "Feedback senden") ?></neo-button-neo-library>
            </div>
        </neo-setting-neo-library><?php
    };
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoSettings\interface_add_neo_setting_20260326')("neo-support--feedback", $settings_render_callback, show_to_editor: true);
    $assets_callback = function () { \NeoLibrary\NeoGlobal\enqueue_js("neo-feedback--settings.js"); };
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
