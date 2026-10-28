<?php
namespace NeoDraw\NeoTutorial; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    $is_demo_screenshot = (bool) \NeoDraw\NeoGlobal\query_param("neo-demo-screenshot");
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoTutorialIsDemoScreenshot", $is_demo_screenshot);
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoTutorialArrowHiddenSelectors", $is_demo_screenshot ? [] : \NeoDraw\NeoGlobal\option__neo_tutorial__hidden_selectors());
});

\NeoDraw\NeoGlobal\register_rest_endpoint("/wp-json/neo/tutorial-hide-arrow", "POST", fn () => \NeoDraw\NeoGlobal\current_user_can__neo_tutorial_hide_arrow(), function ($get_param) {
    $hidden_selectors = \NeoDraw\NeoGlobal\option__neo_tutorial__hidden_selectors(); $hidden_selectors[]= $get_param("dom-selector"); $hidden_selectors = \NeoDraw\NeoGlobal\array_unique_better($hidden_selectors);
    \NeoDraw\NeoGlobal\option__neo_tutorial__hidden_selectors($hidden_selectors);
    return "OKAY";
});

\NeoDraw\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoReset\interface_register_neo_reset_action_20260410')(
        id: "neo-tutorial--reset", button_text: \NeoDraw\NeoGlobal\neo__("Reset tutorial hints", "Tutorial-Hinweise zurücksetzen"), confirm_title: \NeoDraw\NeoGlobal\neo__("Reset tutorial hints?", "Tutorial-Hinweise zurücksetzen?"), confirm_text: \NeoDraw\NeoGlobal\neo__("This will reset all tutorial hints. Are you sure?", "Dies wird alle Tutorial-Hinweise zurücksetzen. Sind Sie sicher?"),
        action_callback: fn () => \NeoDraw\NeoGlobal\option__neo_tutorial__hidden_selectors(delete: true),
    );
});
