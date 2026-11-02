<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function get_image_metadata(string $svg_content, bool $only_read_header = false) {
    $svg_content = str_replace("\n", "", $svg_content);
    $start_tag = "<!-- START - neoDraw metadata -->";
    $end_tag = "<!-- END - neoDraw metadata -->";
    $start_pos = strpos($svg_content, $start_tag);
    if ($start_pos === false) { \NeoRename\NeoGlobal\global_warn_with_module_name("_global-image-metadata", "Invalid metadata in SVG: Start tag not found"); return false; }
    $json_start = $start_pos + strlen($start_tag);
    $end_pos = strpos($svg_content, $end_tag, $json_start);
    if ($end_pos === false && !$only_read_header) { \NeoRename\NeoGlobal\global_warn_with_module_name("_global-image-metadata", "Invalid metadata in SVG: End tag not found"); return false; }
    $raw = ($end_pos === false) ? substr($svg_content, $json_start) : substr($svg_content, $json_start, $end_pos - $json_start);
    $cleaned = \NeoRename\NeoGlobal\preg_replace_better("/^\s*<!--\s*/", "", $raw ?? "");
    $cleaned = \NeoRename\NeoGlobal\preg_replace_better("/\s*-->\s*$/", "", $cleaned ?? "");
    if ($only_read_header) {
        \NeoRename\NeoGlobal\preg_match_better("/\"headerEnd\"\s*:\s*null/", $cleaned, $header_end_matches, PREG_OFFSET_CAPTURE);
        if (count($header_end_matches) === 0) { \NeoRename\NeoGlobal\global_warn_with_module_name("_global-image-metadata", "Invalid metadata in SVG: No headerEnd"); return false; }
        $cut_pos = $header_end_matches[0][1] + strlen($header_end_matches[0][0]);
        $cleaned = substr($cleaned, 0, $cut_pos) . "}";
    }
    $metadata = \NeoRename\NeoGlobal\json_decode_better($cleaned, suppress_error: true);
    if ($metadata === false) { \NeoRename\NeoGlobal\global_warn_with_module_name("_global-image-metadata", "Invalid metadata in SVG JSON"); return false; }
    return $metadata;
}

function get_image_metadata_by_path($file_path) {
    $file_content_first_4_kb = \NeoRename\NeoGlobal\fs_file_get_contents($file_path, false, null, 0, 4096);
    return get_image_metadata($file_content_first_4_kb,true);
}

function get_image_metadata_by_post_id($post_id) {
    $attached_file_path = get_attached_file($post_id);
    if (!\NeoRename\NeoGlobal\fs_file_exists($attached_file_path)) { return null; }
    return get_image_metadata_by_path($attached_file_path);
}

function is_neodraw_image($svg_content) {
    return str_contains($svg_content, "<!-- Created with neoDraw");
}

function is_neodraw_image_path($file_path) {
    if (!str_ends_with($file_path, ".svg")) { return false; }
    $file_content_first_4_kb = \NeoRename\NeoGlobal\fs_file_get_contents($file_path, false, offset: 0, length: 4096);
    return is_neodraw_image($file_content_first_4_kb);
}

function is_neodraw_image_post($image_post) {
    if (!($image_post instanceof \WP_Post)) { return false; }
    if (!($image_post->post_mime_type === "image/svg+xml")) { return false; }
    $attached_file = get_attached_file($image_post->ID);
    if (!\NeoRename\NeoGlobal\fs_file_exists($attached_file)) { return false; }
    return is_neodraw_image_path($attached_file);
}

function get_pixel_variant($svg_content) {
    $start_marker = "<!-- START - Pixel variant -->"; $end_marker = "<!-- END - Pixel variant -->";
    $start_marker_pos = strpos($svg_content, $start_marker); if ($start_marker_pos === false) { return null; }
    $end_marker_pos = strpos($svg_content, $end_marker, $start_marker_pos + strlen($start_marker)); if ($end_marker_pos === false) { return null; }
    $start = strpos($svg_content, "<!-- ", $start_marker_pos + strlen($start_marker)); if (!($start !== false && $start < $end_marker_pos)) { return null; }
    $start += strlen("<!-- "); $end = strpos($svg_content, " -->", $start);
    if (!($end !== false && $end < $end_marker_pos)) { return null; }
    $line = substr($svg_content, $start, $end - $start);

    $parts = explode(" ", $line, 2);
    if (count($parts) !== 2) { return null; }
    $size_quality = $parts[0];
    $base64_url = $parts[1];

    $size_parts = explode("x", $size_quality, 2);
    if (count($size_parts) !== 2) { return null; }
    $width = $size_parts[0];
    $height_quality = $size_parts[1];
    $height_quality_parts = explode("q", $height_quality, 2);
    if (count($height_quality_parts) !== 2) { return null; }
    $height = $height_quality_parts[0];
    $quality = $height_quality_parts[1];

    return ["width" => $width, "height" => $height, "quality" => $quality, "base64_url" => $base64_url];
}
