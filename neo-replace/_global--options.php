<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Central file for provisioning all options with defaults that the plugin can set to ensure that the user's WP instance remains streamlined and well-maintained (clean and orderly). The options are cached in RAM for optimized runtime performance, which significantly improves the user's site performance.
\NeoReplace\NeoGlobal\val("neo_global__option_defaults", []); \NeoReplace\NeoGlobal\val("neo_global__option_autoload", []); function _register_option($option_name, $default_value, bool $autoload) { if ($default_value === null) { \NeoReplace\NeoGlobal\throw_global_exception("Option default value cannot be null"); } \NeoReplace\NeoGlobal\val("neo_global__option_defaults")[$option_name] = $default_value; \NeoReplace\NeoGlobal\val("neo_global__option_autoload")[$option_name] = $autoload; }
function get_or_set_option($name, $value = null, $delete = false) {
    static $cache = [];
    if ($delete && $value !== null) { \NeoReplace\NeoGlobal\throw_global_exception("Cannot set and delete an option at the same time"); }
    if ($delete) { delete_option($name); unset($cache[$name]); return; }
    $autoload = \NeoReplace\NeoGlobal\val("neo_global__option_autoload")[$name] ?? true;
    if ($value === null) {
        if (isset($cache[$name])) { return $cache[$name]; }
        $option_value = get_option($name);
        $default = \NeoReplace\NeoGlobal\val("neo_global__option_defaults")[$name] ?? null;
        if ($default === null) { \NeoReplace\NeoGlobal\throw_global_exception("Option '$name' not registered with a default value in _global--options.php"); }
        if ($option_value === false) {
            get_or_set_option($name, $default);
            $cache[$name] = $default; return $default;
        }
        if (is_string($default)) { $cache[$name] = $option_value; return $option_value; }
        try {
            $value = \NeoReplace\NeoGlobal\json_decode_better($option_value);
            $cache[$name] = $value; return $value;
        } catch (\NeoReplace\NeoGlobal\GlobalException $e) {
            \NeoReplace\NeoGlobal\global_warn_with_module_name("_global--options", "Error decoding option '$name'. Setting default value instead. " . $e->getMessage());
            get_or_set_option($name, $default);
            $cache[$name] = $default; return $default;
        }
    } else {
        $cache[$name] = $value;
        if (is_string(\NeoReplace\NeoGlobal\val("neo_global__option_defaults")[$name] ?? null)) {
            if (!is_string($value)) { \NeoReplace\NeoGlobal\throw_global_exception("Option '$name' must be a string because the default value is a string"); }
            $option_updated = update_option($name, $value, $autoload);
        } else { $option_updated = update_option($name, \NeoReplace\NeoGlobal\json_encode_better($value), $autoload); }
        if (!$option_updated) { return; }
        $encoded_value = \NeoReplace\NeoGlobal\json_encode_better($value); $encoded_value_for_log = in_array($name, ["neo_ai__connection", "neo_ai__free_provider_secret", "neo_tool_sync__pw", "neo_support__support_codes", "neo_support_codes__codes"], true) ? "\"***\"" : $encoded_value; \NeoReplace\NeoGlobal\global_log_with_module_name("_global--options", "Setting option '$name' to value " . (mb_strlen($encoded_value_for_log) > 100 ? mb_substr($encoded_value_for_log, 0, 100) . "…" : $encoded_value_for_log));
    }
}

function get_or_set_option_by_function_name($function_name, $value, $delete) {
    $option_name = explode("option__", $function_name)[1];
    return get_or_set_option($option_name, $value, $delete);
}

_register_option("neo_wp_com",                                                           "neo-wp.com",             true);       function option__neo_wp_com                                                          ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_settings__hide_top_level_menu_enabled",                            true,                     true);       function option__neo_settings__hide_top_level_menu_enabled                           ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_support_codes__codes",                                             [],                       false);      function option__neo_support_codes__codes                                            ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_console_greeting__hide",                                           false,                    true);       function option__neo_console_greeting__hide                                          ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_draw__svg_upload_enabled",                                         false,                    true);       function option__neo_draw__svg_upload_enabled                                        ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_ai__connection", ["provider" => "neoai", "api_key" => "", "model" => null, "api_url" => ""], false);      function option__neo_ai__connection                                                  ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_ai__free_provider_secret",                                         "",                       false);      function option__neo_ai__free_provider_secret                                        ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_ai__token_usage_list",                                             [],                       false);      function option__neo_ai__token_usage_list                                            ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_ai__generated_text_language",                                      "auto",                   true);       function option__neo_ai__generated_text_language                                     ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_ai__custom_prompt_additions", ["title" => "", "alt" => ""],                                  false);      function option__neo_ai__custom_prompt_additions                                     ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_redirect__enabled",                                                true,                     true);       function option__neo_redirect__enabled                                               ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_duplicate__use_today_as_upload_date",                              true,                     true);       function option__neo_duplicate__use_today_as_upload_date                             ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_redirect__list",                                                   [],                       false);      function option__neo_redirect__list                                                  ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_redirect__last_cleanup_timestamp",                                 0,                        true);       function option__neo_redirect__last_cleanup_timestamp                                ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_rename_undo__list",                                                [],                       false);      function option__neo_rename_undo__list                                               ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_image_cachebust__last_change_date",                                0,                        true);       function option__neo_image_cachebust__last_change_date                               ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_optimize__quality",                                                75,                       true);       function option__neo_optimize__quality                                               ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_optimize__avif",                                                   true,                     true);       function option__neo_optimize__avif                                                  ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_optimize__retina_factor",                                          150,                      true);       function option__neo_optimize__retina_factor                                         ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_pro_color_theme__enabled",                                         true,                     true);       function option__neo_pro_color_theme__enabled                                        ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_pro__license_server_message",                                      "",                       true);       function option__neo_pro__license_server_message                                     ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_plugins__redirect_to_settings_after_activation",                   false,                    true);       function option__neo_plugins__redirect_to_settings_after_activation                  ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_global__last_migration_plugin_timestamp_neo_replace", 0,                        true);       function option__neo_global__last_migration_plugin_timestamp_neo_replace($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_manager_installer__suppressed_plugins_list",                       ["neo-optimize"],         true);       function option__neo_manager_installer__suppressed_plugins_list                      ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tutorial__hidden_selectors",                                       [],                       true);       function option__neo_tutorial__hidden_selectors                                      ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_bundler__domain_settings",                                         "",                       true);       function option__neo_bundler__domain_settings                                        ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_demo_content__imported",                                           false,                    true);       function option__neo_demo_content__imported                                          ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_playground__source_server",                                        "",                       true);       function option__neo_playground__source_server                                       ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_debug__shorten_additional_message_enabled",                        true,                     true);       function option__neo_debug__shorten_additional_message_enabled                       ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_debug__show_debug_warnings_enabled",                               true,                     true);       function option__neo_debug__show_debug_warnings_enabled                              ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_debug__show_debug_tools_enabled",                                  true,                     true);       function option__neo_debug__show_debug_tools_enabled                                 ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_debug__error_message_extended_enabled",                            true,                     true);       function option__neo_debug__error_message_extended_enabled                           ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_debug__force_offer_update_enabled",                                false,                    true);       function option__neo_debug__force_offer_update_enabled                               ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_debug__freemius_enabled",                                          false,                    true);       function option__neo_debug__freemius_enabled                                         ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_debug__suppress_pro_enabled",                                      false,                    true);       function option__neo_debug__suppress_pro_enabled                                     ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_debug__db_check_serialize_array_last_check",                       0,                        true);       function option__neo_debug__db_check_serialize_array_last_check                      ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_code__code",                                                  "",                       true);       function option__neo_tool_code__code                                                 ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_code__code_timestamp",                                        0,                        true);       function option__neo_tool_code__code_timestamp                                       ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_database_replace__regex",                                     '\\{_(.*?):(.*?)\\}',     false);      function option__neo_tool_database_replace__regex                                    ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_database_replace__replacement",                               '{_$2:$1}',               false);      function option__neo_tool_database_replace__replacement                              ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_database_replace__modifiers",                                 "si",                     false);      function option__neo_tool_database_replace__modifiers                                ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_database_replace__preview_length",                            50,                       false);      function option__neo_tool_database_replace__preview_length                           ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_sync__pw",                                                    "",                       false);      function option__neo_tool_sync__pw                                                   ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_sync__target_hostnames",                                      [],                       false);      function option__neo_tool_sync__target_hostnames                                     ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_sync__post_slugs",                                            [],                       false);      function option__neo_tool_sync__post_slugs                                           ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }
_register_option("neo_tool_sync__option_names",                                          [],                       false);      function option__neo_tool_sync__option_names                                         ($value = null, $delete = false) { return get_or_set_option_by_function_name(__FUNCTION__, $value, $delete); }

function delete_all_neo_options() {
    global $wpdb;
    $option_names = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", "neo_%")); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* Direct database call to clean up not only autoloaded WP options and prevent leaving behind any lingering database junk. */
    foreach ($option_names as $option_name) {
        if (in_array($option_name, ["neo_tool_sync__target_hostnames", "neo_tool_sync__post_slugs", "neo_tool_sync__option_names", "neo_tool_sync__pw", "neo_tool_code__code", "neo_bundler__domain_settings"])) { continue; }
        delete_option($option_name);
    }
}

\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoReplace\NeoGlobal\register_rest_endpoint("/wp-json/neo/option-get-neo-replace", "GET", fn () => \NeoReplace\NeoGlobal\current_user_can__global_options(), function ($get_param) {
        $option_name = $get_param("name");
        if (!array_key_exists($option_name, \NeoReplace\NeoGlobal\val("neo_global__option_defaults"))) { \NeoReplace\NeoGlobal\throw_global_exception("Option '$option_name' is not registered in _global--options.php"); }
        return get_or_set_option($option_name);
    });
});

\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoReplace\NeoGlobal\register_rest_endpoint("/wp-json/neo/option-set-neo-replace", "POST", fn () => \NeoReplace\NeoGlobal\current_user_can__global_options(), function ($get_param) {
        $option_name = $get_param("name");
        if (!array_key_exists($option_name, \NeoReplace\NeoGlobal\val("neo_global__option_defaults"))) { \NeoReplace\NeoGlobal\throw_global_exception("Option '$option_name' is not registered in _global--options.php"); }
        get_or_set_option($option_name, $get_param("value"));
        return "OKAY";
    });
});
