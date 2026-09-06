<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function get_color_theme_replacements($filename = null, $theme = "theme-auto") {
    if ($theme === "theme-auto") {
        [$theme, $interface_ok] = \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoProColorTheme\interface_get_color_theme_20260106')();
        if (!$interface_ok) { $theme = "theme-neo"; }
    }
    if ($theme === "theme-neo") {
        $color_map = [
            "#ff00ff" => "color(display-p3 1 0 1)",
            "#00ff00" => "color(display-p3 0 1 0)",
        ];

        if ($filename === "neo-library--button-rename-icon.svg")               { $color_map["#00ffff"] = "color(display-p3 0 1 1)"; }
        if ($filename === "neo-library--button-duplicate-icon.svg")            { $color_map["#ffff00"] = "color(display-p3 1 1 0)"; }

        return $color_map;
    }

    return get_color_theme_replacements($filename, theme: "theme-neo");

    return [];
}

function transform_colors($string, $filename = null, $theme = "theme-auto") {
    $replacements = get_color_theme_replacements($filename, theme: $theme);
    return str_ireplace(array_keys($replacements), array_values($replacements), $string);
}
