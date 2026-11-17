<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("load-upload.php", function () {
    if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return; }

    [$neo_library_page_slug, $interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoLibrary\interface_neo_library_menu_page_slug_20250618')();
    if ($interface_ok && \NeoAnimate\NeoGlobal\query_param("page") === $neo_library_page_slug) { return; }

    draw_pagebuilder_init();
    \NeoAnimate\NeoGlobal\add_action_hook("admin_head", function () {
        ?><?php \NeoAnimate\NeoGlobal\backend_page_style_tag_start([]); ?>
            .neo-draw--pb-media-library-create-button .neo-draw--button-logo { height: 1.2em; width: 1.2em; margin-bottom: -0.2em; margin-right: 0.2em; }
        <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_end(); ?><?php
    });

    \NeoAnimate\NeoGlobal\enqueue_js("neo-draw--pb-media-library-general.js");

    \NeoAnimate\NeoGlobal\add_filter_hook("wp_get_attachment_image_attributes", function ($attr, $attachment, $size) {
        if (!is_admin()) { return $attr; }
        if (!array_key_exists("src", $attr)) { return $attr; }
        if (\NeoAnimate\NeoGlobal\is_neodraw_image_post($attachment)) {
            $attr["data-neo-draw--is-neodraw"] = "true";
        } else {
            $attr["data-neo-draw--is-neodraw"] = "false";
        }
        return $attr;
    });

    $mode  = get_user_option( "media_library_mode", get_current_user_id() ) ? get_user_option( "media_library_mode", get_current_user_id() ) : "grid";
    $modes = ["grid", "list"];
    if (in_array(\NeoAnimate\NeoGlobal\query_param("mode"), $modes, true) ) { $mode = \NeoAnimate\NeoGlobal\query_param("mode"); }

    if ($mode === "list") { draw_pagebuilder_init_list(); }
    else {                  draw_pagebuilder_init_grid(); }
});
