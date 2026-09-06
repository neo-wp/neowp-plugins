<?php
namespace NeoOptimize\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Clean and verifiable interface architecture ensuring plugin compatibility modularity in WordPress. When changing a function, its signature has to be updated so that the function name includes the last change date, e.g. interface_add_neo_settings_section_20260411.
function call_interface_func($result, $throw_if_interface_not_ok = false) {
    if (!is_interface_system_available()) { throw new \Exception(esc_html("You can only call an interface function after the entrypoint master_function() was run because only then all plugins are loaded.")); }
    if ($throw_if_interface_not_ok) { return $result; }
    return [$result, true];
}

function call_interface_func_implemented($function_name_with_namespace, $throw_if_interface_not_ok = false) {
    if (!is_interface_system_available()) { throw new \Exception(esc_html("You can only call an interface function ($function_name_with_namespace) after the entrypoint master_function() was run because only then all plugins are loaded.")); }

    $module_namespace = explode("\\", $function_name_with_namespace)[2];
    $function_name    = explode("\\", $function_name_with_namespace)[3];
    if ($module_namespace === "NeoGlobal") {
        return function (...$args) use ($module_namespace, $function_name, $throw_if_interface_not_ok) {
            $active_plugins = \NeoOptimize\NeoEntrypoint\get_neo_active_plugins();
            $this_plugin_from_active_plugins = \NeoOptimize\NeoGlobal\array_filter_better($active_plugins, fn ($p) => ($p["slug"] === \NeoOptimize\NeoGlobal\plugin_slug() && $p["edition"] === \NeoOptimize\NeoGlobal\plugin_edition()))[0] ?? null;
            if ($this_plugin_from_active_plugins === null) { throw new \Exception(esc_html("Cannot find own plugin info in active plugins list.")); }
            $active_plugins = array_values(array_merge([$this_plugin_from_active_plugins], \NeoOptimize\NeoGlobal\array_filter_better($active_plugins, fn ($p) => $p !== $this_plugin_from_active_plugins)));

            $global_interface_functions = [];
            foreach ($active_plugins as $active_plugin) {
                $plugin_namespace_prefix = $active_plugin["namespace_prefix"];
                $full_function_name_in_active_plugin = "\\" . ($plugin_namespace_prefix === "" ? "" : $plugin_namespace_prefix . "\\") . $module_namespace . "\\" . $function_name;
                if (function_exists($full_function_name_in_active_plugin)) { $global_interface_functions[] = $full_function_name_in_active_plugin; }
                else {
                    if ($throw_if_interface_not_ok) { throw new \Exception(esc_html(\NeoOptimize\NeoGlobal\neo__("Probably not all neoPlugins are on the same current version. The global interface function $function_name was not found in " . $active_plugin["slug"] . ".", "Vermutlich sind nicht alle neoPlugins auf dem gleichen aktuellen Stand. Die globale Interface-Funktion $function_name wurde nicht in " . $active_plugin["slug"] . " gefunden."))); }
                    else { return [null, false]; }
                }
            }

            $first_result = null;
            foreach ($global_interface_functions as $index => $global_interface_function) {
                $result = $global_interface_function(...$args);
                if ($index === 0) { $first_result = $result; }
            }

            if ($throw_if_interface_not_ok) { return $first_result; }
            else { return [$first_result, true]; }
        };
    } else {
        foreach (\NeoOptimize\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
            $plugin_namespace_prefix = $active_plugin["namespace_prefix"];
            $full_function_name_in_active_plugin = "\\" . ($plugin_namespace_prefix === "" ? "" : $plugin_namespace_prefix . "\\") . $module_namespace . "\\" . $function_name;
            if (function_exists($full_function_name_in_active_plugin)) {
                return function (...$args) use ($full_function_name_in_active_plugin, $throw_if_interface_not_ok) { return $throw_if_interface_not_ok ? $full_function_name_in_active_plugin(...$args) : [$full_function_name_in_active_plugin(...$args), true]; };
            }
        }
        if ($throw_if_interface_not_ok) { throw new \Exception(esc_html(\NeoOptimize\NeoGlobal\neo__("Probably not all neoPlugins are on the same current version. The interface function $function_name was not found.", "Vermutlich sind nicht alle neoPlugins auf dem gleichen aktuellen Stand. Die Interface-Funktion $function_name wurde nicht gefunden."))); }
        return function (...$args) { return [null, false]; };
    }
}

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    $interface_module_rel_paths = [];
    foreach (\NeoOptimize\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        $plugin_namespace_prefix = $active_plugin["namespace_prefix"] ?? null;
        if ($plugin_namespace_prefix === null) { continue; }
        $plugin_url = null; try { $plugin_url = ("\\" . ($plugin_namespace_prefix === "" ? "" : $plugin_namespace_prefix . "\\") . "NeoGlobal\\plugin_url")(); } catch (\Throwable $err) {}
        if ($plugin_url === null) { continue; }
        $plugin_path = $active_plugin["plugin_path"] ?? null;
        if ($plugin_path === null) { continue; }

        foreach (\NeoOptimize\NeoGlobal\iterate_all_files($plugin_path) as $filepath) {
            if (!str_ends_with($filepath, ".js")) { continue; }
            if (str_contains($filepath, "thirdparty")) { continue; }
            $rel_path = \NeoOptimize\NeoGlobal\make_plugin_file_path_relative($filepath);
            if (isset($interface_module_rel_paths[$rel_path])) { continue; }
            $interface_module_rel_paths[$rel_path] = $plugin_url;
        }
    }

    $interface_module_urls = []; foreach ($interface_module_rel_paths as $rel_path => $url) { $interface_module_urls[$url][] = $rel_path; }
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoInterfaceModuleUrls", $interface_module_urls);
});

function is_interface_system_available() { return \NeoCore\NeoSuperGlobal::$globals["interface_system_ready"] ?? false; }

function is_module_available($module_name, $include_suppressed = false) {
    if (!is_interface_system_available()) { return false; }
    foreach (\NeoOptimize\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (isset($active_plugin["module_files"][$module_name])) { return true; }
        if ($include_suppressed && in_array($module_name, $active_plugin["existing_modules"])) { return true; }
    }
    return false;
}

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    $available_modules = [];
    foreach (\NeoOptimize\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        foreach (array_keys($active_plugin["module_files"]) as $module_name) {
            if (!in_array($module_name, $available_modules)) { $available_modules[] = $module_name; }
        }
    }
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoAvailableModules", $available_modules);
});
