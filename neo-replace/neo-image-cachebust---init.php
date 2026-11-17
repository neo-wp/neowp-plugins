<?php
namespace NeoReplace\NeoImageCachebust; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoReplace\NeoGlobal\frontend_image_hook_register_callback(function ($get_attr, $set_attr, $image_file_path) {
    if (!is_user_logged_in()) { return; }
    $set_attr("src", \NeoReplace\NeoGlobal\add_query_param($get_attr("src"), "neo-image-cachebust", \NeoReplace\NeoGlobal\fs_filemtime($image_file_path)));
});

function interface_update_last_image_cachebust_change_date_20260303() { static $first_run = true; if (!$first_run) { return; } $first_run = false; \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date(time()); }
function interface_get_last_image_cachebust_change_date_20260604() { return \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date(); }
\NeoReplace\NeoGlobal\add_action_hook("add_attachment",    function ($attachment_id) { interface_update_last_image_cachebust_change_date_20260303(); });
\NeoReplace\NeoGlobal\add_action_hook("edit_attachment",   function ($attachment_id) { interface_update_last_image_cachebust_change_date_20260303(); });
\NeoReplace\NeoGlobal\add_action_hook("delete_attachment", function ($attachment_id) { interface_update_last_image_cachebust_change_date_20260303(); });

\NeoReplace\NeoGlobal\add_filter_hook("wp_get_attachment_image_attributes", function ($attr, $attachment, $size) {
    if (!is_admin()) { return $attr; }
    if (!array_key_exists("src", $attr)) { return $attr; }
    $image_file_path = get_attached_file($attachment->ID);
    if ($image_file_path === false || !\NeoReplace\NeoGlobal\fs_file_exists($image_file_path)) { return $attr; }
    $attr["src"] = \NeoReplace\NeoGlobal\add_query_param($attr["src"], "neo-image-cachebust--attachment", \NeoReplace\NeoGlobal\fs_filemtime($image_file_path));
    return $attr;
});
\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    if ((get_current_screen()?->base ?? "") !== "post") { return; }
    if ((get_current_screen()?->post_type ?? "") !== "attachment") { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoImageCachebustLastChangeDateAttachmentEditPage", \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date());
    \NeoReplace\NeoGlobal\enqueue_js("neo-image-cachebust--attachment-edit-page.js");
});

\NeoReplace\NeoGlobal\add_action_hook("template_redirect", "current_screen", function () {
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoImageCachebustLastChangeDateWpMediaSelector", \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date());
});
\NeoReplace\NeoGlobal\add_action_hook("wp_enqueue_media", function () {
    \NeoReplace\NeoGlobal\enqueue_js("neo-image-cachebust--wp-media-selector.js");
});

\NeoReplace\NeoGlobal\add_action_hook("template_redirect", function () {
    if (\NeoReplace\NeoGlobal\query_param("brickspreview") === null) { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_frontend("neoImageCachebustLastChangeDateBricksEditorPreview", \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date());
    \NeoReplace\NeoGlobal\enqueue_js("neo-image-cachebust--bricks-editor.js");
});

\NeoReplace\NeoGlobal\add_action_hook("template_redirect", function () {
    if (\NeoReplace\NeoGlobal\query_param("et_fb") === null) { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_frontend("neoImageCachebustLastChangeDateDiviEditor", \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date());
    \NeoReplace\NeoGlobal\enqueue_js("neo-image-cachebust--divi-editor.js");
});
\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    if ((get_current_screen()?->base ?? "") !== "post") { return; }
    if (!function_exists("et_pb_is_pagebuilder_used") || !et_pb_is_pagebuilder_used(intval(\NeoReplace\NeoGlobal\query_param("post") ?? 0))) { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoImageCachebustLastChangeDateDiviEditor", \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date());
    \NeoReplace\NeoGlobal\enqueue_js("neo-image-cachebust--divi-editor.js");
});

\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    if ((get_current_screen()?->base ?? "") !== "post") { return; }
    if (!(get_current_screen()?->is_block_editor ?? false)) { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoImageCachebustLastChangeDateGutenbergEditor", \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date());
});
\NeoReplace\NeoGlobal\add_action_hook("enqueue_block_editor_assets", function () {
    if ((get_current_screen()?->base ?? "") !== "post") { return; }
    if (!(get_current_screen()?->is_block_editor ?? false)) { return; }
    \NeoReplace\NeoGlobal\enqueue_js("neo-image-cachebust--gutenberg-editor.js");
});

\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    if ((get_current_screen()?->base ?? "") !== "post") { return; }
    if (get_current_screen()?->is_block_editor ?? false) { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoImageCachebustLastChangeDateClassicEditor", \NeoReplace\NeoGlobal\option__neo_image_cachebust__last_change_date());
    \NeoReplace\NeoGlobal\enqueue_js("neo-image-cachebust--classic-editor.js");
});

\NeoReplace\NeoGlobal\add_action_hook("save_post", function (int $post_id, \WP_Post $post, bool $update) {
    $clean_image_cachebust = function ($data, int $depth = 0) use (&$clean_image_cachebust) {
        if ($depth >= 32) { return $data; }
        if (is_string($data)) {
            if (is_serialized($data)) {
                $serialized_data = trim($data);

                $unserialized_data = @unserialize($serialized_data, ["max_depth" => 32 - $depth]);

                if ($unserialized_data === false && $serialized_data !== serialize(false)) { return $data; }
                return serialize($clean_image_cachebust($unserialized_data, $depth + 1));
            }
            return \NeoReplace\NeoGlobal\preg_replace_callback_better("/https?:(?:\\\\)?\\/(?:\\\\)?\\/[^\\s\"']+/", function ($m) {
                $url = $m[0];
                $url = \NeoReplace\NeoGlobal\preg_replace_better("/([?&])neo-image-cachebust(?:--[^=]*)?=\\d+(?:&|$)/", "$1", $url) ?? $url;
                $url = str_replace(["?&", "&&"], ["?", "&"], $url);
                $url = \NeoReplace\NeoGlobal\preg_replace_better("/[?&]$/", "", $url) ?? $url;
                return $url;
            }, $data) ?? $data;
        }
        if (is_array($data))  { foreach ($data as $k => $v) { $data[$k] = $clean_image_cachebust($v, $depth + 1); } return $data; }

        if (is_object($data)) { $cleaned_object = clone $data; foreach ($cleaned_object as $k => $v) { $cleaned_object->$k = $clean_image_cachebust($v, $depth + 1); } return $cleaned_object; }
        return $data;
    };
    static $is_running = false; if ($is_running) { return; } $is_running = true;
    try {
        $cleaned_content = $clean_image_cachebust($post->post_content); if (is_string($cleaned_content) && $cleaned_content !== $post->post_content) { wp_update_post(["ID" => $post_id, "post_content" => wp_slash($cleaned_content)]); }
        foreach (get_post_meta($post_id) as $meta_key => $values) { foreach ($values as $value) { $value_before_cleaning = maybe_serialize($value); $cleaned_value = $clean_image_cachebust($value); if (maybe_serialize($cleaned_value) !== $value_before_cleaning) { update_post_meta($post_id, (string)$meta_key, wp_slash($cleaned_value), $value); } } }
    } finally { $is_running = false; }
});
