<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDuplicate\NeoGlobal\val("neo_global__suppress_log", false);
function suppress_log($suppress = true) { \NeoDuplicate\NeoGlobal\val("neo_global__suppress_log", $suppress); }

function global_log($message, $log_source = null) {
    if (\NeoDuplicate\NeoGlobal\val("neo_global__suppress_log")) { return; }
    if (!\NeoDuplicate\NeoGlobal\is_interface_system_available()) { \NeoDuplicate\NeoGlobal\add_action_hook("neo_init", fn () => global_log($message, $log_source)); return; }
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoLog\interface_global_log_20250702')($message, $log_source);
}
function global_log_with_module_name($module_name_or_global_filename_base, $message, $log_source = null) {
    global_log($message, log_source: $log_source ?? $module_name_or_global_filename_base);
}

function global_warn($message, $log_source = null) {
    if (\NeoDuplicate\NeoGlobal\val("neo_global__suppress_log")) { return; }
    if (!\NeoDuplicate\NeoGlobal\is_interface_system_available()) {
        \NeoDuplicate\NeoGlobal\add_action_hook("neo_init", fn () => global_warn("[⚠️ Stack Trace lost because of delayed warning - set breakpoint in _global--log.php to see stack trace] " . $message, $log_source));
        return;
    }
    [$_, $interface_ok] = \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoLog\interface_global_warn_20250325')($message, $log_source);
    if (!$interface_ok) {
        error_log("global_warn: " . $message); /* phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log */ // Error log is not for debugging here. It is only a fallback that's useful to the user if the neoLog-internal interface is not available because of an error.
    }
}
function global_warn_with_module_name($module_name_or_global_filename_base, $message, $log_source = null) {
    global_warn($message, log_source: $log_source ?? $module_name_or_global_filename_base);
}

function admin_info($message, $title = null) {
    if (\NeoDuplicate\NeoGlobal\val("neo_global__suppress_log")) { return; }
    if (!\NeoDuplicate\NeoGlobal\is_interface_system_available()) { \NeoDuplicate\NeoGlobal\add_action_hook("neo_init", fn () => admin_info($message, $title)); return; }
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoLog\interface_admin_info_20251129')($message, $title);
}
function admin_success($message, $title = null) {
    if (\NeoDuplicate\NeoGlobal\val("neo_global__suppress_log")) { return; }
    if (!\NeoDuplicate\NeoGlobal\is_interface_system_available()) { \NeoDuplicate\NeoGlobal\add_action_hook("neo_init", fn () => admin_success($message, $title)); return; }
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoLog\interface_admin_success_20251129')($message, $title);
}

function admin_warn($message, $title = null) {
    if (\NeoDuplicate\NeoGlobal\val("neo_global__suppress_log")) { return; }
    if (!\NeoDuplicate\NeoGlobal\is_interface_system_available()) { \NeoDuplicate\NeoGlobal\add_action_hook("neo_init", fn () => admin_warn($message, $title)); return; }
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoLog\interface_admin_warn_20251129')($message, $title);
}

function admin_error($message, $title = null) {
    if (\NeoDuplicate\NeoGlobal\val("neo_global__suppress_log")) { return; }
    if (!\NeoDuplicate\NeoGlobal\is_interface_system_available()) { \NeoDuplicate\NeoGlobal\add_action_hook("neo_init", fn () => admin_error($message, $title)); return; }
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoLog\interface_admin_error_20251129')($message, $title);
}
