<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// WordPress Hook to apply deterministic, file-based cache-busting so browsers only invalidate cached images when the underlying media file has actually changed.
\NeoAnimate\NeoGlobal\val("neo_global__frontend_image_hook_callbacks", []);
function frontend_image_hook_register_callback($callback) {
    \NeoAnimate\NeoGlobal\val("neo_global__frontend_image_hook_callbacks")[] = $callback;
}

function frontend_image_hook_filter_html($html) {
    if ($html === "") { return $html; }

    $process_image = function ($img_attributes) {
        static $cache = []; $cache_hash = md5(serialize($img_attributes)); if (isset($cache[$cache_hash])) { return $cache[$cache_hash]; }

        $set_changes = [];
        $get_attr = function ($key) use (&$img_attributes) { return $img_attributes[$key] ?? null; };
        $set_attr = function ($key, $value) use (&$img_attributes, &$set_changes) { $set_changes[$key] = $value; if ($value === null) { unset($img_attributes[$key]); return; } else { $img_attributes[$key] = $value; } };

        if (!\NeoAnimate\NeoGlobal\is_url_internal($get_attr("src"))) { return []; }

        try {
            $image_file_path = \NeoAnimate\NeoGlobal\get_path_from_internal_url($get_attr("src"));
        } catch (\Throwable $e) { return []; }

        if (!\NeoAnimate\NeoGlobal\fs_file_exists($image_file_path)) { return []; }

        foreach (\NeoAnimate\NeoGlobal\val("neo_global__frontend_image_hook_callbacks") as $callback) {
            try {
                $callback($get_attr, $set_attr, $image_file_path);
            } catch (\Throwable $e) { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global-frontend-image-hook", $e->getMessage() . " - Failed to process image: " . $get_attr("src")); }
        }

        $cache[$cache_hash] = $set_changes;
        return $cache[$cache_hash];
    };

    $img_url_regex_raw = "https?:\/\/(www\.)?(?:[-a-z0-9@:%._\+~#=]{1,256}\.[a-z0-9()]{1,32}|[-a-z0-9@:%_\+~#=]{1,256})\b([-a-z0-9()@:%_\+.~\/=]*)\.(?:jpg|jpeg|jfif|webp|avif|png|gif|bmp|tif|tiff|heic|heif|svg|ico)(?![a-z0-9\.])(\?[-a-z0-9()@:%_\+.~#&\/=;]*)?(?!&quot;)";
    $split_css_url_match = function ($img_url) {
        $open_parentheses = 0;
        for ($i = 0; $i < strlen($img_url); $i++) {
            if ($img_url[$i] === "(")  { $open_parentheses++; continue; }
            if ($img_url[$i] !== ")")  {                      continue; }
            if ($open_parentheses > 0) { $open_parentheses--; continue; }
            $suffix = substr($img_url, $i);
            $img_url = substr($img_url, 0, $i);
            if (\NeoAnimate\NeoGlobal\preg_match_all_better("/[&?](neo-global--processed-[a-z0-9-]+)=([^&#)]*)/i", $suffix, $processed_param_matches, PREG_SET_ORDER)) {
                foreach ($processed_param_matches as $processed_param_match) {
                    $img_url = \NeoAnimate\NeoGlobal\add_query_param($img_url, $processed_param_match[1], urldecode($processed_param_match[2]));
                }
                $suffix = \NeoAnimate\NeoGlobal\preg_replace_better("/[&?]neo-global--processed-[a-z0-9-]+=[^&#)]*/i", "", $suffix);
            }
            return [$img_url, $suffix];
        }
        return [$img_url, ""];
    };
    $img_tag_regex_raw = "<img[^>]*" . $img_url_regex_raw . "[^>]*>";
    $img_tag_or_url_regex = "/(?<img_tag>$img_tag_regex_raw)|(?<img_url>$img_url_regex_raw)/i";
    $html = \NeoAnimate\NeoGlobal\preg_replace_callback_better($img_tag_or_url_regex, function ($matches) use ($html, $process_image, $img_url_regex_raw, $split_css_url_match) {
        if (($matches["img_tag"][1] ?? -1) !== -1) {
            $img_tag = $matches["img_tag"][0];

            if (\NeoAnimate\NeoGlobal\preg_match_better("/(?:^|\s)[a-zA-Z0-9_:-]+\s*=\s*\\\\[\"']/", $img_tag)) { return $img_tag; }

            if (str_contains($img_tag, "data-neo-global--processed-neo-animate")) { return $img_tag; }

            $attr_regex_pattern = '/(?<spacing_before_attr>\s+)(?<key>[a-zA-Z0-9_\-:]+)(?:(?<equals>=)(?:"(?<value1>[^"]*)"|\'(?<value2>[^\']*)\'|(?<value3>[^ \t\r\n"\'>]*)))?/';
            $attributes = [];
            if (\NeoAnimate\NeoGlobal\preg_match_all_better($attr_regex_pattern, $img_tag, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $attr_key   = $match["key"];
                    $attr_value = ($match["value1"] ?? "") ?: ($match["value2"] ?? "") ?: ($match["value3"] ?? "") ?: "";
                    if (isset($attributes[$attr_key])) { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global-frontend-image-hook", "Duplicate attribute in image tag: $attr_key in $img_tag. It was deduplicated."); }
                    $attributes[$attr_key] = $attr_value;
                }
            }

            $attribute_changes = null;
            $attribute_changes_remembered_for_attribute_key = null;
            $attributes_before = $attributes;
            foreach ($attributes as $attr_key => $attr_value) {
                if ($attr_value === null) { continue; }
                if ($attr_key === "href") { continue; }
                $attributes[$attr_key] = \NeoAnimate\NeoGlobal\preg_replace_callback_better("/$img_url_regex_raw/i", function ($match) use (&$attributes_before, &$process_image, &$attr_key, &$attribute_changes, &$attribute_changes_remembered_for_attribute_key, &$split_css_url_match) {
                    [$img_url, $img_url_suffix] = $split_css_url_match($match[0]);
                    $fake_attributes = $attributes_before; $fake_attributes["src"] = $img_url;
                    if (in_array($attr_key, ["data-large_image", "data-full"], true)) { $fake_attributes["data-neo-optimize--width"] = "2560px"; $fake_attributes["data-neo-optimize--height"] = "2560px"; }
                    $set_changes = $process_image($fake_attributes);
                    $result = $set_changes["src"] ?? $fake_attributes["src"];
                    unset($set_changes["src"]);
                    if (($attr_key === "srcset" && !in_array($attribute_changes_remembered_for_attribute_key, ["src", "data-src", "srcset"])) || ($attr_key === "src" && !in_array($attribute_changes_remembered_for_attribute_key, ["data-src"])) || ($attr_key === "data-src" && !in_array($attribute_changes_remembered_for_attribute_key, []))) { $attribute_changes = $set_changes; $attribute_changes_remembered_for_attribute_key = $attr_key; }
                    return $result . $img_url_suffix;
                }, $attr_value);
            }

            foreach ($attribute_changes ?? [] as $attr_key => $attr_value) { if ($attr_value === null) { unset($attributes[$attr_key]); } else { $attributes[$attr_key] = $attr_value; } }

            $attributes["data-neo-global--processed-neo-animate"] = "true";

            $additional_attributes_string = ""; foreach ($attributes as $key => $value) { if (array_key_exists($key, $attributes_before)) { continue; } $additional_attributes_string .= " " . $key . ($value === null ? "" : ('="' . str_replace('"', '&quot;', $value) . '"')); }
            $new_img_tag = \NeoAnimate\NeoGlobal\preg_replace_better('/\s*\/?\s*>$/', $additional_attributes_string . "$0", $img_tag);

            $new_img_tag = \NeoAnimate\NeoGlobal\preg_replace_callback_better($attr_regex_pattern, function ($match) use (&$attributes, &$attribute_changes) {
                $spacing_before_attr = $match["spacing_before_attr"];
                $key = $match["key"];
                if (!array_key_exists($key, $attributes)) { return ""; }
                $value = $attributes[$key];
                $equals = $match["equals"] ?? null;
                if (empty($equals) && $value !== "") { $equals = "="; }
                $quote_type = !empty($match["value1"]) ? '"' : (!empty($match["value2"]) ? "'" : (!empty($match["value3"]) ? "" : null));
                if (empty($quote_type) && $value !== "") $quote_type ??= '"';
                if ($quote_type !== "" && $quote_type !== null && str_contains($value, $quote_type)) { $value = $quote_type === '"' ? str_replace('"', '&quot;', $value) : ($quote_type === "'" ? str_replace("'", '&#39;', $value) : $value); }
                     if ($value === "" && array_key_exists($key, $attribute_changes ?? [])) { return $spacing_before_attr . $key; }
                else if ($value === "") { return $match[0]; }
                return $spacing_before_attr . $key . ($equals ?: "=") . $quote_type . $value . $quote_type;
            }, $new_img_tag);

            return $new_img_tag;
        } else {
            if (\NeoAnimate\NeoGlobal\preg_match_better('/href\s*=\s*["\']$/i', substr($html, max(0, $matches["img_url"][1] - 20), min(20, $matches["img_url"][1])))) { return $matches["img_url"][0]; }
            [$img_url, $img_url_suffix] = $split_css_url_match($matches["img_url"][0]);
            if (str_contains($img_url, "neo-global--processed-neo-animate=true")) { return $img_url . $img_url_suffix; }
            if (!\NeoAnimate\NeoGlobal\is_url_internal($img_url)) { return $img_url . $img_url_suffix; }

            $img_url = $process_image(["src" => $img_url])["src"] ?? $img_url;

            $img_url = \NeoAnimate\NeoGlobal\add_query_param($img_url, "neo-global--processed-neo-animate", "true");
            return $img_url . $img_url_suffix;
        }
    }, $html, flags: PREG_OFFSET_CAPTURE);
    return $html;
}
\NeoAnimate\NeoGlobal\add_filter_hook(
    "the_content",
    "elementor/frontend/the_content",
    "vc_shortcode_output",
    "et_module_process_display_conditions",
    "fl_builder_after_render_shortcodes",
    "bricks/frontend/render_data",
    "render_block",
    "wp_get_attachment_image",
    "post_thumbnail_html",
    "get_custom_logo",
    "get_avatar",
    "widget_text",
    "widget_text_content",
    "widget_custom_html_content",
    "widget_block_content",
    "walker_nav_menu_start_el",
    "wp_nav_menu_items",
    "comment_text",
    "get_the_archive_description",
    "the_excerpt",
    "the_content_feed",
    "the_excerpt_rss",
    "wp_get_attachment_link",
    "prepend_attachment",
    "embed_oembed_html",
    "oembed_result",
    "wp_video_shortcode",
    function ($html) {
        if (is_admin()) { return $html; }
        if (!is_string($html)) { return $html; }
        return frontend_image_hook_filter_html($html);
    }
);
