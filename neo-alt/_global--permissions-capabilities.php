<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function _register_js_permission($function_name) {
    if (did_action("init") && !doing_action("init")) { return; }
    \NeoAlt\NeoGlobal\add_action_hook("init", function () use (&$function_name) {
        \NeoAlt\NeoGlobal\enqueue_js_variable_backend(lcfirst(str_replace("_", "", ucwords($function_name, "_"))), (__NAMESPACE__ . "\\" . $function_name)());
    });
}

function current_user_can__global_endpoint__refresh_nonce()         { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__global_endpoint__refresh_nonce");
function current_user_can__global_options()                         { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__global_options");
function current_user_can__global_db_entries_usage()                { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__global_db_entries_usage");
function current_user_can__global_db_entries_details()              { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__global_db_entries_details");
function current_user_can__neo_console_greeting()                   { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_console_greeting");
function current_user_can__neo_debug()                              { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_debug");
function current_user_can__neo_demo_content__import()               { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_demo_content__import");
function current_user_can__neo_domain_settings__update()            { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_domain_settings__update");
function current_user_can__neo_draw()                               { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_draw");
function current_user_can__neo_duplicate()                          { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_duplicate");
function current_user_can__neo_animate()                            { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_animate");
function current_user_can__neo_freemius__delete_license_data()      { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_freemius__delete_license_data");
function current_user_can__neo_freemius_installer()                 { return current_user_can("install_plugins"); }    _register_js_permission("current_user_can__neo_freemius_installer");
function current_user_can__neo_bundler__freemius_events_analysis()  { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_bundler__freemius_events_analysis");
function current_user_can__neo_gpt_engine__change_reasons()         { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_gpt_engine__change_reasons");
function current_user_can__neo_library__view()                      { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_library__view");
function current_user_can__neo_library__edit()                      { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_library__edit");
function current_user_can__neo_manager_installer()                  { return current_user_can("install_plugins"); }    _register_js_permission("current_user_can__neo_manager_installer");
function current_user_can__neo_manager_wporg()                      { return current_user_can("install_plugins"); }    _register_js_permission("current_user_can__neo_manager_wporg");
function current_user_can__neo_alt()                                { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_alt");
function current_user_can__neo_ai()                                 { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_ai");
function current_user_can__neo_ai__settings()                       { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_ai__settings");
function current_user_can__neo_optimize__settings()                 { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_optimize__settings");
function current_user_can__neo_plugins__uninstall()                 { return current_user_can("delete_plugins"); }     _register_js_permission("current_user_can__neo_plugins__uninstall");
function current_user_can__neo_pro__delete_license_server_message() { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_pro__delete_license_server_message");
function current_user_can__neo_pro_installer()                      { return current_user_can("install_plugins"); }    _register_js_permission("current_user_can__neo_pro_installer");
function current_user_can__neo_replace()                            { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_replace");
function current_user_can__neo_rename()                             { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_rename");
function current_user_can__neo_rename__settings()                   { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_rename__settings");
function current_user_can__neo_log__view()                          { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_log__view");
function current_user_can__neo_log__js()                            { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_log__js");
function current_user_can__neo_reset()                              { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_reset");
function current_user_can__neo_settings__see_all_settings()         { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_settings__see_all_settings");
function current_user_can__neo_support__support_codes()             { return current_user_can("manage_options") && is_super_admin(); } _register_js_permission("current_user_can__neo_support__support_codes");
function current_user_can__neo_symlink()                            { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_symlink");
function current_user_can__neo_tutorial_hide_arrow()                { return current_user_can("edit_others_posts"); }  _register_js_permission("current_user_can__neo_tutorial_hide_arrow");
function current_user_can__neo_tool_passkey()                       { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_tool_passkey");
function current_user_can__neo_tool_mail_send()                     { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_tool_mail_send");
function current_user_can__neo_tool_array_view()                    { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_tool_array_view");
function current_user_can__neo_tool_database_replace()              { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__neo_tool_database_replace");
function current_user_can__website()                                { return current_user_can("edit_theme_options"); } _register_js_permission("current_user_can__website");
function current_user_can__save_domain_settings()                   { return current_user_can("manage_options"); }     _register_js_permission("current_user_can__save_domain_settings");
function current_user_can__public_endpoint()                        { return "public"; }                               _register_js_permission("current_user_can__public_endpoint");

function capability__neo_library()            { return "edit_others_posts"; }
function capability__neo_alt()                { return "edit_others_posts"; }
function capability__neo_settings()           { return "edit_others_posts"; }
function capability__neo_freemius_installer() { return "install_plugins"; }
function capability__website()                { return "edit_theme_options"; }
function capability__tool()                   { return "manage_options"; }
