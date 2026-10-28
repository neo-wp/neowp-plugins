<?php
namespace NeoReplace\NeoReplace; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoReplace\NeoGlobal\add_filter_hook("media_row_actions", function ($actions, $img_post, $detached) {
    if (!\NeoReplace\NeoGlobal\current_user_can__neo_replace()) { return $actions; }
    $img_url = wp_get_attachment_url($img_post->ID);
    if (!$img_url) { return $actions; }
    $actions["neo_replace"] = '<button class="button-link neo-replace--media-library-list-inline-replace-button"'
                            . ' style="color: gray; pointer-events: none; cursor: default;"'
                            . ' data-neo-replace--img-url="' . esc_attr($img_url) . '">'
                            . \NeoReplace\NeoGlobal\neo__("neoReplace", "neoReplace") . '</button>';
    return $actions;
});

\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    global $pagenow;
    if ($pagenow !== "upload.php") { return; }
    if (!\NeoReplace\NeoGlobal\current_user_can__neo_replace()) { return; }
    [$neo_library_page_slug, $interface_ok] = \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoLibrary\interface_neo_library_menu_page_slug_20250618')(); if ($interface_ok && \NeoReplace\NeoGlobal\query_param("page") === $neo_library_page_slug) { return; }
    \NeoReplace\NeoGlobal\enqueue_js("neo-replace--classic-wp-media-library-list.js");
    \NeoReplace\NeoGlobal\enqueue_js("neo-replace--classic-wp-media-library-grid.js");
});
