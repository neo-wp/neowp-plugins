<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// This function deactivates the escaping of slashes from PHP because we always use php_to_js_object() to inject inline JS, which already handles the escaping of </script> properly.
function json_encode_better($content_object, $pretty_print = false) {
    try { $json_encoded = json_encode($content_object, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | ($pretty_print ? JSON_PRETTY_PRINT : 0)); }
    catch (\JsonException $error) {
        if ($error->getCode() !== JSON_ERROR_UTF8) { throw $error; }
        if (\function_exists(__NAMESPACE__ . "\\global_warn")) { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global--better-json-functions", "Invalid UTF-8 was replaced while encoding JSON."); }
        $json_encoded = json_encode($content_object, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR | ($pretty_print ? JSON_PRETTY_PRINT : 0));
    }
    return $json_encoded;
}

function php_to_js_object($php_object) { $js_object = json_encode_better($php_object); return str_replace("<", "\\u003C", $js_object); }

function json_decode_better($json_string, $suppress_error = false) {
    $parsed_json = null;
    $json_start_index = -1;
    $length = strlen($json_string);
         if (str_ends_with($json_string, "false")){ $parsed_json = false;$json_start_index = $length - strlen("false");}
    else if (str_ends_with($json_string, "true")){ $parsed_json = true;$json_start_index = $length - strlen("true");}
    else if (str_ends_with($json_string, "null")){ $parsed_json = null;$json_start_index = $length - strlen("null");}
    else if (\NeoAnimate\NeoGlobal\preg_match_better('/(-?(?:\d+\.\d+|\d+)(?:e[+\-]?\d+))$/i', $json_string, $matches)){ $parsed_json = json_decode($matches[1]);$json_start_index = $length - strlen($matches[1]);}
    else if (\NeoAnimate\NeoGlobal\preg_match_better('/(-?\d+\.\d+)$/',$json_string, $matches)){ $parsed_json = json_decode($matches[1]);$json_start_index = $length - strlen($matches[1]);}
    else if (\NeoAnimate\NeoGlobal\preg_match_better('/(-?\d+)$/',$json_string, $matches)){ $parsed_json = json_decode($matches[1]);$json_start_index = $length - strlen($matches[1]);}
    else {
        for ($i = 0; $i < $length; $i++) {
            if (in_array($json_string[$i], ["{", "[", "\""])) {
                $parsed_json = json_decode(substr($json_string, $i), associative: true);
                if ($parsed_json !== null) { $json_start_index = $i; break; }
            }
        }
    }

    if ($json_start_index === -1 && $suppress_error) { return false; }
    if ($json_start_index === -1) { \NeoAnimate\NeoGlobal\throw_global_exception("JSON Decoding Error. Tried to decode: " . $json_string); }
    $php_warning_string = substr($json_string, 0, $json_start_index);
    if ($php_warning_string !== "") { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global--better-json-functions", "Warning from PHP while decoding JSON: $php_warning_string"); }
    return $parsed_json;
}
