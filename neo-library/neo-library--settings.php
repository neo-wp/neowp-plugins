<?php
namespace NeoLibrary\NeoLibrary; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoLibrary\NeoGlobal\add_filter_hook("submenu_file", function ($submenu_file) {
    global $plugin_page;
    if ($plugin_page === get_neo_library_menu_page_slug()) {
        remove_submenu_page("upload.php", get_neo_library_menu_page_slug());
        return "upload.php";
    }
    return $submenu_file;
});

\NeoLibrary\NeoGlobal\add_action_hook("neo_init", function () {
    foreach (\NeoLibrary\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (!in_array($active_plugin["slug"], ["neo-library", "neo-media"], true)) { continue; }
        \NeoLibrary\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]) . ":16", function ($links) {
            array_unshift($links, '<a href="' . esc_url(admin_url("upload.php?page=" . get_neo_library_menu_page_slug())) . '">' . esc_html(\NeoLibrary\NeoGlobal\neo__("Open neoLibrary", "neoLibrary öffnen")) . '</a>');
            return $links;
        });
    }
});
