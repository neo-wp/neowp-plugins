<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_draw_pagebuilder_elementor_inline_svg_db_clear_20251118() {
    global $wpdb;

    $success = true;
    $post_ids = $wpdb->get_col("SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = '_elementor_inline_svg'"); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
    $neodraw_post_ids = [];
    $deleted_count = 0;
    foreach ($post_ids as $post_id) {
        if (\NeoAnimate\NeoGlobal\is_neodraw_image_post(get_post($post_id))) {
            $neodraw_post_ids[] = intval($post_id);
        }
    }
    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Clear Elementor inline SVG cache for neoDraw: " . \NeoAnimate\NeoGlobal\json_encode_better(["postIdsWithInlineSvg" => count($post_ids), "neodrawPostIdsCount" => count($neodraw_post_ids), "neodrawPostIds" => array_slice($neodraw_post_ids, 0, 50)]));
    foreach ($neodraw_post_ids as $post_id) {
        if (delete_post_meta($post_id, "_elementor_inline_svg")) { $deleted_count++; }
        else { $success = false; }
    }

    \NeoAnimate\NeoGlobal\global_log_with_module_name("neo-draw", "Optimize postmeta after neoDraw Elementor inline SVG cleanup: " . \NeoAnimate\NeoGlobal\json_encode_better(["deletedCount" => $deleted_count, "success" => $success]));
    $wpdb->query("OPTIMIZE TABLE $wpdb->postmeta"); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
    if (!$success) { \NeoAnimate\NeoGlobal\global_warn_with_module_name("neo-draw", "Could not delete some Elementor inline SVG postmeta entries for neoDraw images from database"); }
}

\NeoAnimate\NeoGlobal\add_filter_hook("update_post_metadata", function ($check, $post_id, $meta_key, $meta_value, $prev_value) {
    if ($meta_key === "_elementor_inline_svg") {
        if (\NeoAnimate\NeoGlobal\is_neodraw_image_post(get_post($post_id))) { return false; }
    }
    return null;
});
