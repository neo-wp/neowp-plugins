<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("wp_enqueue_scripts", function () {
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--transform-img-to-svg.frontend.js");
});

\NeoMotion\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-draw", \NeoMotion\NeoGlobal\add_or_update_query_params(admin_url("upload.php"), ["mode" => "list", "force-old-media-lib" => "true"]));
});

function interface_get_create_url_20260824($media_library_url) {
    return \NeoMotion\NeoGlobal\add_or_update_query_params($media_library_url, ["neo-draw--create" => "true"]);
}
\NeoMotion\NeoGlobal\add_action_hook("neo_init", function () {
    foreach (\NeoMotion\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (!in_array($active_plugin["slug"], ["neo-draw", "neo-media"], true)) { continue; }
        \NeoMotion\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]) . ":14", function ($links) {
            [$media_library_url, $neo_library_interface_ok] = \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoLibrary\interface_neo_library_menu_page_url_20250618')(); if (!$neo_library_interface_ok) { $media_library_url = admin_url("upload.php"); }
            array_unshift($links, '<a href="' . esc_url(interface_get_create_url_20260824($media_library_url)) . '">' . esc_html(\NeoMotion\NeoGlobal\neo__("Create neoDraw", "neoDraw erstellen")) . '</a>');
            return $links;
        });
    }
});
