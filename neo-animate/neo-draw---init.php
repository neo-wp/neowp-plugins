<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("wp_enqueue_scripts", function () {
    \NeoAnimate\NeoGlobal\enqueue_js("neo-draw--transform-img-to-svg.frontend.js");
});

\NeoAnimate\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-draw", \NeoAnimate\NeoGlobal\add_or_update_query_params(admin_url("upload.php"), ["mode" => "list", "force-old-media-lib" => "true"]));
});

function interface_get_create_url_20260824($media_library_url) {
    return \NeoAnimate\NeoGlobal\add_or_update_query_params($media_library_url, ["neo-draw--create" => "true"]);
}
\NeoAnimate\NeoGlobal\add_action_hook("neo_init", function () {
    foreach (\NeoAnimate\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (!in_array($active_plugin["slug"], ["neo-draw", "neo-media"], true)) { continue; }
        \NeoAnimate\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]) . ":14", function ($links) {
            [$media_library_url, $neo_library_interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoLibrary\interface_neo_library_menu_page_url_20250618')(); if (!$neo_library_interface_ok) { $media_library_url = admin_url("upload.php"); }
            array_unshift($links, '<a href="' . esc_url(interface_get_create_url_20260824($media_library_url)) . '">' . esc_html(\NeoAnimate\NeoGlobal\neo__("Create neoDraw", "neoDraw erstellen")) . '</a>');
            return $links;
        });
    }
});
