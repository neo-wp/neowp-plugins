<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function draw_pagebuilder_init_list() {
    \NeoAnimate\NeoGlobal\enqueue_js("neo-draw--pb-media-library-list.js");
    \NeoAnimate\NeoGlobal\enqueue_css("neo-draw--pb-media-library-list-item.css");

    \NeoAnimate\NeoGlobal\add_filter_hook("media_row_actions", function ($actions, $img_post, $detached) {
        if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return $actions; }
        if (!str_starts_with((string) get_post_mime_type($img_post), "image/")) { return $actions; }
        $img_url = wp_get_attachment_url($img_post->ID); if (!$img_url) { return $actions; }
        $is_neodraw_image = \NeoAnimate\NeoGlobal\is_neodraw_image_post($img_post);
        ob_start(); if ($is_neodraw_image) { \NeoAnimate\NeoGlobal\echo_neo__("neoEdit", "neoEditor"); } else { \NeoAnimate\NeoGlobal\echo_neo__("Copy & neoEdit", "Kopieren & neoEditor"); } $button_text = ob_get_clean() ?: "";

        $actions["neo_draw__edit"] = '<button type="button" class="neo-draw--media-library-inline-edit-button button-link"'
                                    . ' data-neo-draw--img-url="' . esc_attr($img_url) . '"'
                                    . ' data-neo-draw--is-neodraw="' . ($is_neodraw_image ? "true" : "false") . '"'
                                    . '>' . $button_text . '</button>';
        return $actions;
    });
}
