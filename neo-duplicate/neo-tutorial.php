<?php
namespace NeoDuplicate\NeoTutorial; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDuplicate\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    $is_demo_screenshot = (bool) \NeoDuplicate\NeoGlobal\query_param("neo-demo-screenshot");
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoTutorialIsDemoScreenshot", $is_demo_screenshot);
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoTutorialArrowHiddenSelectors", $is_demo_screenshot ? [] : \NeoDuplicate\NeoGlobal\option__neo_tutorial__hidden_selectors());
});

\NeoDuplicate\NeoGlobal\register_rest_endpoint("/wp-json/neo/tutorial-hide-arrow", "POST", fn () => \NeoDuplicate\NeoGlobal\current_user_can__neo_tutorial_hide_arrow(), function ($get_param) {
    $hidden_selectors = \NeoDuplicate\NeoGlobal\option__neo_tutorial__hidden_selectors(); $hidden_selectors[]= $get_param("dom-selector"); $hidden_selectors = \NeoDuplicate\NeoGlobal\array_unique_better($hidden_selectors);
    \NeoDuplicate\NeoGlobal\option__neo_tutorial__hidden_selectors($hidden_selectors);
    return "OKAY";
});

\NeoDuplicate\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoReset\interface_register_neo_reset_action_20260410')(
        id: "neo-tutorial--reset", button_text: \NeoDuplicate\NeoGlobal\neo__("Reset tutorial hints", "Tutorial-Hinweise zurücksetzen"), confirm_title: \NeoDuplicate\NeoGlobal\neo__("Reset tutorial hints?", "Tutorial-Hinweise zurücksetzen?"), confirm_text: \NeoDuplicate\NeoGlobal\neo__("This will reset all tutorial hints. Are you sure?", "Dies wird alle Tutorial-Hinweise zurücksetzen. Sind Sie sicher?"),
        action_callback: fn () => \NeoDuplicate\NeoGlobal\option__neo_tutorial__hidden_selectors(delete: true),
    );
});
