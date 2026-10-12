<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Enhanced hook wrapper for cleaner multi-hook registration to improves clarity, safety, and debugging.
function add_action_hook(...$args) { _multiple_hook_registration(false, ...$args); }
function add_filter_hook(...$args) { _multiple_hook_registration(true,  ...$args); }

\NeoAlt\NeoGlobal\val("neo_global__original_hook_callbacks", []);
function func_id($value) { if (is_object($value)) { return 'obj_' . spl_object_id($value); } else if (is_array($value)) { return 'arr_' . md5(json_encode($value)); } else { return 'val_' . md5((string)$value); } }
function get_original_hook_callback($hook_callback) { return \NeoAlt\NeoGlobal\val("neo_global__original_hook_callbacks")[func_id($hook_callback)] ?? null; }

function _multiple_hook_registration(bool $use_filter, ...$args) {
    if (count($args) < 2) { throw new \InvalidArgumentException("Error: Too few parameters."); }
    $callback = array_pop($args);
    if (is_int($callback)) { throw new \InvalidArgumentException("Error: Last parameter is an integer. Provide the priority in the hook name (e.g. admin_init:20) and omit the number of function parameters (found using reflection)."); }
    if (!$callback instanceof \Closure) { throw new \InvalidArgumentException("Error: Last parameter is no anonymous function (Closure)."); }
    if (empty($args)) { throw new \InvalidArgumentException("Error: No hooks found."); }

    $num_params = (new \ReflectionFunction($callback))->getNumberOfParameters();
    $caller_rel_path = function_exists('\NeoAlt\NeoGlobal\get_caller_rel_path') ? \NeoAlt\NeoGlobal\get_caller_rel_path() : null;
    foreach ($args as $hook_name_with_prio) {
        if (is_int($hook_name_with_prio)) { throw new \InvalidArgumentException("Error: Hook name is an integer. Provide the priority in the hook name (e.g. admin_init:20) and omit the number of function parameters (found using reflection)."); }
        if (!is_string($hook_name_with_prio)) { throw new \InvalidArgumentException("Error: Hook name is not a string."); }
        $hook_name = explode(":", $hook_name_with_prio)[0];
        $hook_prio = explode(":", $hook_name_with_prio)[1] ?? "10"; if ($hook_prio === "max") { $hook_prio = PHP_INT_MAX; } else { $hook_prio = intval($hook_prio); }
        $wrapped_callback = function (...$args) use ($use_filter, $hook_name, $caller_rel_path, $callback) {
            return \NeoAlt\NeoEntrypoint\suppress_plugin_on_error(function () use ($use_filter, $hook_name, $caller_rel_path, $callback, $args) {
                performance_checkpoint("hook " . ($use_filter ? "filter" : "action") . " " . $hook_name, caller_rel_path: $caller_rel_path, is_start: true);
                $value = $callback(...$args);
                performance_checkpoint("hook " . ($use_filter ? "filter" : "action") . " " . $hook_name, caller_rel_path: $caller_rel_path, is_end: true);
                return $value;
            });
        };
        \NeoAlt\NeoGlobal\val("neo_global__original_hook_callbacks")[func_id($wrapped_callback)] = $callback;
        ($use_filter ? "add_filter" : "add_action")($hook_name, $wrapped_callback, $hook_prio, $num_params);
    }
}

function performance_checkpoint($message, $caller_rel_path = null, $time = null, $is_start = false, $is_end = false) {
    static $performance_file = __DIR__ . "/_global--hook-performance-exclude-wporg.php";
    static $performance_file_exists = null; $performance_file_exists ??= file_exists($performance_file);
    if (!$performance_file_exists) { return; }
    static $performance_file_included = false; if (!$performance_file_included) { (fn () => require_once($performance_file))(); $performance_file_included = true; }
    \NeoAlt\NeoGlobal\performance_checkpoint_implementation($message, $caller_rel_path, $time, $is_start, $is_end);
}
