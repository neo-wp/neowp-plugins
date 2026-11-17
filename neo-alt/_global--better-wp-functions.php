<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function slug_to_post_id($slug) {
    if ($slug === "" || $slug === null) { return null; }
    $post_type = null; if (str_contains($slug, "/")) { $exploded_slug = explode("/", $slug); $post_type = $exploded_slug[0]; $slug = $exploded_slug[1]; }
    $posts = get_posts(["name" => $slug, "post_type" => $post_type ?? array_values(get_post_types()), "post_status" => \NeoAlt\NeoGlobal\array_diff_better(array_values(get_post_stati()), ["trash", "auto-draft"]), "numberposts" => 1, "fields" => "ids"]);
    return $posts ? (is_array($posts) ? $posts[0] : null) : null;
}

function slug_to_post($slug) {
    $post_id = slug_to_post_id($slug);
    if ($post_id === null) { return null; }
    return get_post($post_id);
}

function slug_to_image_url($slug) {
    $post_id = slug_to_post_id("attachment/$slug");
    if ($post_id === null) { return null; }
    return wp_get_attachment_url($post_id) ?: null;
}

function post_meta($post_id, $meta_key) {
    if (!metadata_exists("post", $post_id, $meta_key)) { return null; }
    $meta_value = get_post_meta($post_id, $meta_key, single: true);
    return $meta_value;
}

function uploads_dir() {
    return untrailingslashit(wp_upload_dir()["basedir"]);
}

function uploads_url() {
    return untrailingslashit(wp_upload_dir()["baseurl"]);
}
