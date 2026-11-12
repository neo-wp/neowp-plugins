<?php
namespace NeoOptimize\NeoTutorial; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoOptimize\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    $is_demo_screenshot = (bool) \NeoOptimize\NeoGlobal\query_param("neo-demo-screenshot");
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoTutorialIsDemoScreenshot", $is_demo_screenshot);
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoTutorialArrowHiddenSelectors", $is_demo_screenshot ? [] : \NeoOptimize\NeoGlobal\option__neo_tutorial__hidden_selectors());
});

\NeoOptimize\NeoGlobal\register_rest_endpoint("/wp-json/neo/tutorial-hide-arrow", "POST", fn () => \NeoOptimize\NeoGlobal\current_user_can__neo_tutorial_hide_arrow(), function ($get_param) {
    $hidden_selectors = \NeoOptimize\NeoGlobal\option__neo_tutorial__hidden_selectors(); $hidden_selectors[]= $get_param("dom-selector"); $hidden_selectors = \NeoOptimize\NeoGlobal\array_unique_better($hidden_selectors);
    \NeoOptimize\NeoGlobal\option__neo_tutorial__hidden_selectors($hidden_selectors);
    return "OKAY";
});

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoReset\interface_register_neo_reset_action_20260410')(
        id: "neo-tutorial--reset", button_text: \NeoOptimize\NeoGlobal\neo__("Reset tutorial hints", "Tutorial-Hinweise zurücksetzen"), confirm_title: \NeoOptimize\NeoGlobal\neo__("Reset tutorial hints?", "Tutorial-Hinweise zurücksetzen?"), confirm_text: \NeoOptimize\NeoGlobal\neo__("This will reset all tutorial hints. Are you sure?", "Dies wird alle Tutorial-Hinweise zurücksetzen. Sind Sie sicher?"),
        action_callback: fn () => \NeoOptimize\NeoGlobal\option__neo_tutorial__hidden_selectors(delete: true),
    );
});
