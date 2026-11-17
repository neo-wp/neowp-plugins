<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function register_backend_page($get_query_param, \Closure $permission_callback, \Closure $html_output_callback) {
    if (!str_starts_with($get_query_param, "neo-")) { \NeoDuplicate\NeoGlobal\throw_global_exception("GET parameter of the registered backend page must start with 'neo-' to avoid conflicts with other GET parameters."); }
    if (\NeoDuplicate\NeoGlobal\query_param($get_query_param) === null) { return; }
    \NeoDuplicate\NeoGlobal\add_action_hook("current_screen:0", function () use ($get_query_param, $permission_callback, $html_output_callback) {
        if ($permission_callback() !== true) { wp_die("Your user role does not have permission to access this backend page.", "Forbidden", ["response" => 403]); }
        $html_output_callback();
        exit;
    });
}

function get_backend_page_url($get_query_param) { return admin_url("?$get_query_param"); }
