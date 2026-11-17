<?php
namespace NeoLibrary\NeoProColorTheme; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_get_color_theme_20260106() {
    return "theme-neo";
}

\NeoLibrary\NeoGlobal\add_action_hook("neo_init:9", function () {
    $render_callback = function () {
        ?><neo-setting-neo-library> <?php
            \NeoLibrary\NeoGlobal\echo_switch_for_option("neo_pro_color_theme__enabled", \NeoLibrary\NeoGlobal\neo__("Display Icons in neoPro colors (blue and gold)", "Icons in neoPro-Farben anzeigen (blau und gold)"), only_enabled_if_pro: true, js_function_on_change: "window.neoProColorThemeReloadOnChange");
        ?></neo-setting-neo-library><?php
    };
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoSettings\interface_add_neo_setting_20260326')("neo-pro-color-theme", $render_callback);
});
