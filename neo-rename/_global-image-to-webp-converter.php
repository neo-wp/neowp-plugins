<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/image-converter-source-neo-rename", "GET", fn () => \NeoRename\NeoGlobal\current_user_can__global_endpoint__refresh_nonce(), function ($get_param) {
        $image_url = \NeoRename\NeoGlobal\remove_all_query_params($get_param("image-url"));
        if ($image_url === "") { \NeoRename\NeoGlobal\throw_global_exception("Image URL is required.", status_code: 400); }
        $max_size = intval($get_param("max-size") ?: 1920); $min_short_side = intval($get_param("min-short-side") ?: 200);
        if (!($max_size >= 1 && $max_size <= 10000 && $min_short_side >= 1 && $min_short_side <= 10000)) { \NeoRename\NeoGlobal\throw_global_exception("Image converter source dimensions are invalid.", status_code: 400); }
        $decoded_image_url = \NeoRename\NeoGlobal\percent_decode_invalid_utf8_url_bytes($image_url);
        $uploads_url_parts = wp_parse_url(\NeoRename\NeoGlobal\uploads_url()); $image_url_parts = wp_parse_url($decoded_image_url);
        $uploads_host = strtolower($uploads_url_parts["host"] ?? ""); $image_host = strtolower($image_url_parts["host"] ?? ""); $uploads_path = untrailingslashit($uploads_url_parts["path"] ?? ""); $image_path = $image_url_parts["path"] ?? "";
        if (!($uploads_host !== "" && $image_host === $uploads_host && ($image_path === $uploads_path || str_starts_with($image_path, $uploads_path . "/")))) { return ["imageUrl" => $image_url]; }
        $image_id = attachment_url_to_postid($decoded_image_url);
        if ($image_id === 0) { return ["imageUrl" => $image_url]; }
        $attachment_metadata = wp_get_attachment_metadata($image_id); $original_image_path = get_attached_file($image_id);
        $original_width = $attachment_metadata["width"] ?? 0; $original_height = $attachment_metadata["height"] ?? 0;
        if (!($original_width > 0 && $original_height > 0) || !is_string($original_image_path) || max($original_width, $original_height) <= $max_size) { return ["imageUrl" => $image_url]; }
        $source_candidates = [];
        $add_source_candidate = function ($candidate_url, $candidate_path, $candidate_width, $candidate_height) use (&$source_candidates, $original_width, $original_height, $max_size, $min_short_side) {
            if (!($candidate_width >= $min_short_side && $candidate_height >= $min_short_side && max($candidate_width, $candidate_height) >= $max_size)) { return; }
            if (!(abs(($candidate_width * $original_height) / ($candidate_height * $original_width) - 1) <= 0.02)) { return; }
            $source_candidates[] = ["url" => $candidate_url, "longestSide" => max($candidate_width, $candidate_height), "bytes" => \NeoRename\NeoGlobal\fs_is_file($candidate_path) ? \NeoRename\NeoGlobal\fs_filesize($candidate_path) : PHP_INT_MAX];
        };

        foreach (($attachment_metadata["sizes"] ?? []) as $size_name => $size) {
            if (!(($size["width"] ?? 0) > 0 && ($size["height"] ?? 0) > 0)) { continue; }
            $size_source = wp_get_attachment_image_src($image_id, $size_name);
            if (!is_array($size_source) || ($size_source[0] ?? "") === "") { continue; }
            $add_source_candidate($size_source[0], dirname($original_image_path) . "/" . $size["file"], $size["width"], $size["height"]);
        }

        [$cached_image_paths, $interface_ok] = \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoOptimize\interface_list_cached_images_20250302')($decoded_image_url);
        if ($interface_ok) { foreach ($cached_image_paths as $cached_image_url => $cached_image_path) { $cached_image_size = wp_getimagesize($cached_image_path); if (!is_array($cached_image_size)) { continue; } $add_source_candidate($cached_image_url, $cached_image_path, $cached_image_size[0], $cached_image_size[1]); } }
        usort($source_candidates, fn ($candidate_a, $candidate_b) => $candidate_a["longestSide"] <=> $candidate_b["longestSide"] ?: $candidate_a["bytes"] <=> $candidate_b["bytes"]);
        return ["imageUrl" => \NeoRename\NeoGlobal\percent_encode_invalid_utf8_url_bytes($source_candidates[0]["url"] ?? $decoded_image_url)];
    });
});
