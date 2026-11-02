<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function cookie_get_sanitized($name) {
    return is_string($_COOKIE[$name] ?? null) ? wp_strip_all_tags(wp_unslash($_COOKIE[$name] ?? null)) : null;
}

function cookie_set($name, $value, $expire = 0, $path = "/", $domain = "", $secure = false, $httponly = false) {
    $success = setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    $_COOKIE[$name] = $value;
    if (!$success) { \NeoDuplicate\NeoGlobal\global_warn_with_module_name("_global--cookie", "Failed to set cookie: " . $name); }
}

function cookie_delete($name, $path = "/", $domain = "", $secure = false, $httponly = false) {
    $success = setcookie($name, "", -1, $path, $domain, $secure, $httponly);
    unset($_COOKIE[$name]);
    if (!$success) { \NeoDuplicate\NeoGlobal\global_warn_with_module_name("_global--cookie", "Failed to delete cookie: " . $name); }
}

function cookie_exists($name) {
    return isset($_COOKIE[$name]);
}
