<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("load-upload.php", function () {
    if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }

    [$neo_library_page_slug, $interface_ok] = \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoLibrary\interface_neo_library_menu_page_slug_20250618')();
    if ($interface_ok && \NeoDraw\NeoGlobal\query_param("page") === $neo_library_page_slug) { return; }

    draw_pagebuilder_init();
    \NeoDraw\NeoGlobal\add_action_hook("admin_head", function () {
        ?><?php \NeoDraw\NeoGlobal\backend_page_style_tag_start([]); ?>
            .neo-draw--pb-media-library-create-button .neo-draw--button-logo { height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.2em; }
        <?php \NeoDraw\NeoGlobal\backend_page_style_tag_end(); ?><?php
    });

    \NeoDraw\NeoGlobal\enqueue_js("neo-draw--pb-media-library-general.js");

    \NeoDraw\NeoGlobal\add_filter_hook("wp_get_attachment_image_attributes", function ($attr, $attachment, $size) {
        if (!is_admin()) { return $attr; }
        if (!array_key_exists("src", $attr)) { return $attr; }
        if (\NeoDraw\NeoGlobal\is_neodraw_image_post($attachment)) {
            $attr["data-neo-draw--is-neodraw"] = "true";
        } else {
            $attr["data-neo-draw--is-neodraw"] = "false";
        }
        return $attr;
    });

    $mode  = get_user_option( "media_library_mode", get_current_user_id() ) ? get_user_option( "media_library_mode", get_current_user_id() ) : "grid";
    $modes = ["grid", "list"];
    if (in_array(\NeoDraw\NeoGlobal\query_param("mode"), $modes, true) ) { $mode = \NeoDraw\NeoGlobal\query_param("mode"); }

    if ($mode === "list") { draw_pagebuilder_init_list(); }
    else {                  draw_pagebuilder_init_grid(); }
});
