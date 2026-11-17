<?php
namespace NeoRename\NeoRename; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\add_action_hook("edit_form_advanced", function () {
    if (get_post_type() !== "attachment") { return; }
    \NeoRename\NeoGlobal\enqueue_js("neo-rename--post-detail-page.js");
});

\NeoRename\NeoGlobal\add_filter_hook("media_row_actions", function ($actions, $img_post, $detached) {
    if (!\NeoRename\NeoGlobal\current_user_can__neo_rename()) { return $actions; }

    $img_url = wp_get_attachment_url($img_post->ID);
    $actions["neo_rename"] = '<button class="button-link neo-rename--media-library-list-inline-rename-button"'
                            . ' style="color: gray; pointer-events: none; cursor: default;"'
                            . ' data-neo-rename--img-url="' . $img_url . '">'
                            . \NeoRename\NeoGlobal\neo__("neoRename", "neoRename") . '</button>';
    return $actions;
});
\NeoRename\NeoGlobal\add_action_hook("load-upload.php", function () {
    if (!\NeoRename\NeoGlobal\current_user_can__neo_rename()) { return; }
    [$neo_library_page_slug, $interface_ok] = \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoLibrary\interface_neo_library_menu_page_slug_20250618')(); if ($interface_ok && \NeoRename\NeoGlobal\query_param("page") === $neo_library_page_slug) { return; }
    \NeoRename\NeoGlobal\enqueue_js("neo-rename--classic-wp-media-library-list.js");
    \NeoRename\NeoGlobal\enqueue_js("neo-rename--classic-wp-media-library-grid.js");
    \NeoRename\NeoGlobal\enqueue_js("neo-rename--classic-wp-media-library-bulk-button.js");
});

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    [$neo_rename_settings_section_url, $interface_ok] = \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-rename"); if (!$interface_ok) { $neo_rename_settings_section_url = ""; }
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoRenameSettingsSectionUrl", $neo_rename_settings_section_url);
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoRenameMediaSettingsUrl", admin_url("options-media.php"));
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-rename", \NeoRename\NeoGlobal\add_or_update_query_params(admin_url("upload.php"), ["mode" => "list", "neo-library--suppress-redirect" => "true"]) . "#neo-rename--open-tutorial");
});

\NeoRename\NeoGlobal\register_migration("2026-08-02", function () {
    delete_option("neo_rename_alt_text__generation_enabled");
});
