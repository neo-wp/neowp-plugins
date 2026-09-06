<?php
namespace NeoOptimize\NeoOptimize; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function optimize_unique_image_filename_prefix($img_url) {
    $file_name = \NeoOptimize\NeoGlobal\make_internal_url_relative_to_uploads(\NeoOptimize\NeoGlobal\remove_all_query_params($img_url));
    $file_name = str_replace("~", "~-", $file_name);
    $file_name = str_replace("/", "~", $file_name);
    $file_name = pathinfo($file_name, PATHINFO_FILENAME);
    $file_name = $file_name . "~~";

    return $file_name;
}
function interface_optimize_unique_image_filename_prefix_20251216($img_url) { return optimize_unique_image_filename_prefix($img_url); }
function optimize_format_support($format, $algorithm = null) {
    $format = strtolower($format);
    if (!in_array($format, ["webp", "avif"], true)) { \NeoOptimize\NeoGlobal\throw_global_exception("Unsupported neoOptimize output format: " . $format); }
    if ($algorithm === null) { return optimize_format_support($format, "imagick") || optimize_format_support($format, "gd"); }
    if (!in_array($algorithm, ["imagick", "gd"], true)) { \NeoOptimize\NeoGlobal\throw_global_exception("Unsupported neoOptimize resize algorithm: " . $algorithm); }
    static $format_support = [];
    $cache_key = $algorithm . ":" . $format;
    if (array_key_exists($cache_key, $format_support)) { return $format_support[$cache_key]; }
    if ($algorithm === "imagick" && !(extension_loaded("imagick") && class_exists("Imagick"))) { return $format_support[$cache_key] = false; }
    if ($algorithm === "gd" && !(extension_loaded("gd") && function_exists("gd_info"))) { return $format_support[$cache_key] = false; }
    if ($algorithm === "gd" && $format === "avif" && \NeoOptimize\NeoGlobal\is_playground()) { return $format_support[$cache_key] = false; }
    $format_supported = false;
    try {
        if ($algorithm === "imagick" && in_array(strtoupper($format), \Imagick::queryFormats(strtoupper($format)), true)) { $image = new \Imagick(); try { $image->newImage(1, 1, new \ImagickPixel("transparent")); $image->setImageFormat($format); $format_supported = $image->getImagesBlob() !== ""; } finally { $image->clear(); $image->destroy(); } }
        if ($algorithm === "gd") { $gd_info = gd_info(); $gd_declares_format = $format === "avif" ? (($gd_info["AVIF Support"] ?? false) && function_exists("imageavif")) : (($gd_info["WebP Support"] ?? false) && function_exists("imagewebp")); if ($gd_declares_format) { $image = imagecreatetruecolor(1, 1); if ($image !== false) { $buffer_level = ob_get_level(); ob_start(); try { $encode_success = $format === "avif" ? imageavif($image, null, 75) : imagewebp($image, null, 75); $encoded_data = ob_get_clean(); $format_supported = $encode_success && is_string($encoded_data) && $encoded_data !== ""; } finally { if (ob_get_level() > $buffer_level) { ob_end_clean(); } } } } }
    } catch (\Throwable $e) { $format_supported = false; }
    if (!$format_supported) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "neoOptimize cannot encode " . strtoupper($format) . " with " . $algorithm . ". Falling back to another encoder or the original image. (deduplicated)"); }
    return $format_support[$cache_key] = $format_supported;
}

function optimize_modify_frontend_img($get_attr, $set_attr, $image_file_path, $output_format = null) {
    $cache_dir = \NeoOptimize\NeoGlobal\cache_path("neo-optimize");
    if ($cache_dir === false) { return; }

    if (!\NeoOptimize\NeoGlobal\fs_file_exists($image_file_path)) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Image file does not exist: $image_file_path"); return; }

    $resize_timeout = 30;

    $img_url = $get_attr("src");
    if (str_contains($img_url, "/neo-optimize/")) { return; }
    if (str_ends_with($image_file_path, ".svg")) {
        $file_content_first_4_kb = \NeoOptimize\NeoGlobal\fs_file_get_contents($image_file_path, false, null, 0, 4096);
        $is_neodraw = \NeoOptimize\NeoGlobal\is_neodraw_image($file_content_first_4_kb);

        if (!$is_neodraw) { return; }
    } else { $is_neodraw = false; }

    $gd_formats = ["jpg", "jpeg", "png", "gif", "webp", "avif", "bmp", "wbmp", "xpm"];
    $imagick_formats = ["jpg", "jpeg", "png", "gif", "webp", "avif", "heic", "heif", "tif", "tiff", "bmp", "ico", "psd", "xcf", "svg", "raw", "hdr", "jp2"];
    if (!($is_neodraw || in_array(strtolower(pathinfo($image_file_path, PATHINFO_EXTENSION)), $gd_formats) || in_array(strtolower(pathinfo($image_file_path, PATHINFO_EXTENSION)), $imagick_formats))) { return; }

    $image_id = attachment_url_to_postid(\NeoOptimize\NeoGlobal\remove_all_query_params($img_url)) ?: null;

    $resize_algorithm = null;
    if (extension_loaded("imagick") && class_exists("Imagick"))    { $resize_algorithm = "imagick"; }
    else if (extension_loaded("gd") && function_exists("gd_info")) { $resize_algorithm = "gd"; }
    else                                                           { $resize_algorithm = "none"; }

    $size_algorithm = $resize_algorithm;
    if (str_ends_with($image_file_path, ".gif") && extension_loaded("gd") && function_exists("gd_info")) { $size_algorithm = "gd"; }

    $custom_width_value  = $get_attr("data-neo-optimize--width")  ?? null;
    $custom_height_value = $get_attr("data-neo-optimize--height") ?? null;
    $style_width_value   = \NeoOptimize\NeoGlobal\preg_match_better("/(?:^|;)\s*width\s*:\s*([a-zA-Z0-9.%]+)/",  $get_attr("style") ?? "", $style_width_match)  === 1 ? $style_width_match[1]  : null;
    $style_height_value  = \NeoOptimize\NeoGlobal\preg_match_better("/(?:^|;)\s*height\s*:\s*([a-zA-Z0-9.%]+)/", $get_attr("style") ?? "", $style_height_match) === 1 ? $style_height_match[1] : null;
    $width_attr_value    = $get_attr("width")  !== null ? ($get_attr("width")  . "px") : null;
    $height_attr_value   = $get_attr("height") !== null ? ($get_attr("height") . "px") : null;

    if ($custom_width_value === "") { $custom_width_value = null; } if ($custom_height_value === "") { $custom_height_value = null; }
    if ($style_width_value  === "") { $style_width_value  = null; } if ($style_height_value  === "") { $style_height_value  = null; }
    if ($width_attr_value   === "") { $width_attr_value   = null; } if ($height_attr_value   === "") { $height_attr_value   = null; }

    $used_width_string = null; $used_height_string = null;
         if ($custom_width_value !== null || $custom_height_value !== null) { $used_width_string = $custom_width_value; $used_height_string = $custom_height_value; }
    else if ($style_width_value  !== null || $style_height_value  !== null) { $used_width_string = $style_width_value;  $used_height_string = $style_height_value;  }
    else if ($width_attr_value   !== null || $height_attr_value   !== null) { $used_width_string = $width_attr_value;   $used_height_string = $height_attr_value;   }

    $screen_max_width   = 1920;
    $screen_max_height  = 1080;

    $used_width_number  = $used_width_string  !== null ? floatval(\NeoOptimize\NeoGlobal\preg_replace_better("/[^0-9]/", "", $used_width_string))  : null;
    $used_height_number = $used_height_string !== null ? floatval(\NeoOptimize\NeoGlobal\preg_replace_better("/[^0-9]/", "", $used_height_string)) : null;
    $used_width_unit    = $used_width_string  !== null ?          \NeoOptimize\NeoGlobal\preg_replace_better("/[0-9]/",  "", $used_width_string)   : null;
    $used_height_unit   = $used_height_string !== null ?          \NeoOptimize\NeoGlobal\preg_replace_better("/[0-9]/",  "", $used_height_string)  : null;
    if ($used_width_number  === 0) { $used_width_number  = null; }
    if ($used_height_number === 0) { $used_height_number = null; }

    $used_width = null; $used_height = null;
         if ($used_width_unit  ===  "px") { $used_width  = $used_width_number; }
    else if ($used_width_unit  ===   "%") { $used_width  = $used_width_number / 100 * $screen_max_width;  }
    else if ($used_width_unit  ===  "em") { $used_width  = $used_width_number * 16       * 1.5; }
    else if ($used_width_unit  === "rem") { $used_width  = $used_width_number * 16       * 1.5; }
    else if ($used_width_unit  ===  "ex") { $used_width  = $used_width_number * 16 * 0.5 * 1.5; }
    else if ($used_width_unit  ===  "lh") { $used_width  = $used_width_number * 16 * 1.2 * 1.5; }
    else if ($used_width_unit  ===  "vw") { $used_width  = $used_width_number / 100 * $screen_max_width;  }
    else if ($used_width_unit  ===  "vh") { $used_width  = $used_width_number / 100 * $screen_max_height; }
    else if ($used_width_unit  ===  "")   { $used_width  = $used_width_number; }
    else                                  { $used_width  = null; }
         if ($used_height_unit ===  "px") { $used_height = $used_height_number; }
    else if ($used_height_unit ===   "%") { $used_height = $used_height_number / 100 * $screen_max_height; }
    else if ($used_height_unit ===  "em") { $used_height = $used_height_number * 16       * 1.5; }
    else if ($used_height_unit === "rem") { $used_height = $used_height_number * 16       * 1.5; }
    else if ($used_height_unit ===  "ex") { $used_height = $used_height_number * 16 * 0.5 * 1.5; }
    else if ($used_height_unit ===  "lh") { $used_height = $used_height_number * 16 * 1.2 * 1.5; }
    else if ($used_height_unit ===  "vw") { $used_height = $used_height_number / 100 * $screen_max_width;  }
    else if ($used_height_unit ===  "vh") { $used_height = $used_height_number / 100 * $screen_max_height; }
    else if ($used_height_unit ===  "")   { $used_height = $used_height_number; }
    else                                  { $used_height = null; }

    $get_image_size = function ($img_path) use (&$size_algorithm) {
        try {
            if ($size_algorithm === "imagick") {
                $imagick = new \Imagick(); $imagick->pingImage($img_path);
                try { $orig_img_size = $imagick->getImageGeometry(); }
                finally { $imagick->clear(); $imagick->destroy(); }
                if ($orig_img_size !== false) { return [$orig_img_size["width"], $orig_img_size["height"]]; }
                \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not get the size of the image $img_path for resizing using imagick.");
                $size_algorithm = "gd";
            }
            if ($size_algorithm === "gd")  {
                $orig_img_size = getimagesize($img_path);
                if ($orig_img_size !== false) { return [$orig_img_size[0], $orig_img_size[1]]; }
                \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not get the size of the image $img_path for resizing using gd.");
                $size_algorithm = "none";
            }
            if ($size_algorithm === "none") {
                $orig_img_size = \NeoOptimize\NeoGlobal\post_meta(get_post_thumbnail_id($img_path), "_wp_attachment_metadata");
                if (is_array($orig_img_size) && ($orig_img_size["width"] ?? 0) > 0 && ($orig_img_size["height"] ?? 0) > 0) { return [$orig_img_size["width"], $orig_img_size["height"]]; }
                \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not get the size of the image $img_path for resizing.");
            }
        } catch (\Throwable $e) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not get the size of the image $img_path for resizing: " . $e->getMessage()); }
        return false;
    };
    if ($is_neodraw) {
        $svg_meta = \NeoOptimize\NeoGlobal\get_image_metadata($file_content_first_4_kb,true);
        if ($svg_meta === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Broken SVG metadata of image file $image_file_path"); return; }
        if (!($svg_meta["height"] > 0 && $svg_meta["width"] > 0)) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Invalid SVG size in metadata of image file $image_file_path"); return; }
        $orig_svg_ratio = $svg_meta["width"] / $svg_meta["height"];
    } else {
        $orig_img_size = $get_image_size($image_file_path);
        if ($orig_img_size === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not get the size of the image file $image_file_path for resizing."); return; }
        if (!($orig_img_size[0] > 0 && $orig_img_size[1] > 0)) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Invalid image size of image file $image_file_path"); return; }
        $orig_svg_ratio = $orig_img_size[0] / $orig_img_size[1];
    }

    $object_fit_cover_size = function ($container_width, $container_height, $target_aspect_ratio) use ($screen_max_width, $screen_max_height) {
        $covered_width  = null; $covered_height = null;
        if ($container_width !== null && $container_height !== null) {
            $covered_width = $container_width; $covered_height = $container_height;
            if ($container_width > $container_height * $target_aspect_ratio) { $covered_height = $container_width  / $target_aspect_ratio; }
            else                                                             { $covered_width  = $container_height * $target_aspect_ratio; }
        } else if ($container_width !== null && $container_height === null) {
            $covered_width = $container_width; $covered_height = $container_width / $target_aspect_ratio;
        } else if ($container_width === null && $container_height !== null) {
            $covered_height = $container_height; $covered_width = $container_height * $target_aspect_ratio;
        } else if ($container_width === null && $container_height === null) {
            $covered_width = $screen_max_width; $covered_height = $screen_max_height;
            if ($covered_width < $covered_height * $target_aspect_ratio) { $covered_height = $covered_width  / $target_aspect_ratio; }
            else                                                         { $covered_width  = $covered_height * $target_aspect_ratio; }
        } else { \NeoOptimize\NeoGlobal\throw_global_exception("Unexpected error: Could not determine the image size."); }
        return [$covered_width, $covered_height];
    };

    list($resized_width, $resized_height) = $object_fit_cover_size($used_width, $used_height, $orig_svg_ratio);
    if (!($resized_width > 0 && $resized_height > 0)) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Invalid resize target size for image $image_file_path. Width: $resized_width, Height: $resized_height"); return; }

    $embedded_webp_quality_attr = $get_attr("data-neo-optimize--quality") !== null ? intval($get_attr("data-neo-optimize--quality")) : null;
    $embedded_webp_quality_match_found = \NeoOptimize\NeoGlobal\preg_match_better("/r([0-9]{1,3})q([0-9]{1,3})/", $img_url, $embedded_webp_quality_match);
    $embedded_webp_quality_filename = $embedded_webp_quality_match_found === 1 ? intval($embedded_webp_quality_match[2]) : null;
    $embedded_webp_quality = $embedded_webp_quality_attr ?? $embedded_webp_quality_filename ?? null;
    $embedded_webp_quality ??= \NeoOptimize\NeoGlobal\option__neo_optimize__quality();

    $resize_algorithm_forced = false; $retina_factor = 1.5;

    $calculate_resize_target_size = function ($source_width, $source_height, $target_width, $target_height) use ($retina_factor) {
        if (!($source_width > 0 && $source_height > 0 && $target_width > 0 && $target_height > 0 && $retina_factor > 0)) { \NeoOptimize\NeoGlobal\throw_global_exception("Invalid image size or retina factor for resizing."); }
        $target_width *= $retina_factor; $target_height *= $retina_factor;
        $overscaled_factor_width = $target_width / $source_width; $overscaled_factor_height = $target_height / $source_height;
        $max_over_scale_factor = max($overscaled_factor_width, $overscaled_factor_height);
        if ($max_over_scale_factor > 1) { $target_width /= $max_over_scale_factor; $target_height /= $max_over_scale_factor; }
        $aspect_ratio = $target_width / $target_height; $target_width = intval(ceil($target_width)); $target_height = intval(round($target_width / $aspect_ratio));
        return [$target_width, $target_height, $target_width / $retina_factor, $target_height / $retina_factor];
    };

    $choose_resize_output = function ($file_path, $preferred_algorithm, $algorithm_forced) use ($output_format) {
        $imagick_available = extension_loaded("imagick") && class_exists("Imagick");
        $gd_available      = extension_loaded("gd")      && function_exists("gd_info");
        $imagick_supports_webp = optimize_format_support("webp", "imagick"); $gd_supports_webp = optimize_format_support("webp", "gd");

        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $is_animated_gif = false;
        if ($file_extension === "gif" && $imagick_available) {
            try { $imagick = new \Imagick(); $imagick->pingImage($file_path); try { $is_animated_gif = $imagick->getNumberImages() > 1; } finally { $imagick->clear(); $imagick->destroy(); } }
            catch (\Throwable $e) { $is_animated_gif = false; }
        }
        if ($is_animated_gif) { return $imagick_supports_webp ? ["imagick", "webp"] : ["none", "webp"]; }

        if ($output_format === "webp" || ($output_format === null && !\NeoOptimize\NeoGlobal\option__neo_optimize__avif())) { if ($preferred_algorithm === "imagick" && $imagick_supports_webp) { return ["imagick", "webp"]; } if ($preferred_algorithm === "gd" && $gd_supports_webp) { return ["gd", "webp"]; } if (!$algorithm_forced && $imagick_supports_webp) { return ["imagick", "webp"]; } if (!$algorithm_forced && $gd_supports_webp) { return ["gd", "webp"]; } }

        $imagick_supports_avif = optimize_format_support("avif", "imagick"); $gd_supports_avif = optimize_format_support("avif", "gd");
        if ($preferred_algorithm === "gd") { if ($gd_supports_avif) { return ["gd", "avif"]; } if ($gd_supports_webp) { return ["gd", "webp"]; } if (!$algorithm_forced && $imagick_supports_avif) { return ["imagick", "avif"]; } if (!$algorithm_forced && $imagick_supports_webp) { return ["imagick", "webp"]; } return ["none", "webp"]; }

        $imagick_avif_transparency_safe = false;
        $imagick_version_found = false;
        if ($preferred_algorithm === "imagick" && $imagick_supports_avif) {
            $imagick_version_string = \Imagick::getVersion()["versionString"] ?? "";
            $imagick_version_found = \NeoOptimize\NeoGlobal\preg_match_better("/ImageMagick ([0-9]+)\.([0-9]+)\.([0-9]+)-([0-9]+)/", $imagick_version_string, $imagick_version_match) === 1;
            $imagick_avif_transparency_safe = $imagick_version_found && (intval($imagick_version_match[1]) >= 7 || version_compare($imagick_version_match[1] . "." . $imagick_version_match[2] . "." . $imagick_version_match[3] . "." . $imagick_version_match[4], "6.9.12.68", ">="));
            if ($imagick_avif_transparency_safe) { return ["imagick", "avif"]; }
        }

        $has_transparency = false;
        if ($preferred_algorithm === "imagick" && $imagick_supports_avif && in_array($file_extension, ["png", "gif", "webp", "avif"])) {
            try {
                if ($imagick_available) { $imagick = new \Imagick(); $imagick->readImage($file_path); try { $has_transparency = $imagick->getImageAlphaChannel(); } finally { $imagick->clear(); $imagick->destroy(); } }
                else if ($gd_available) { $gd_image = imagecreatefromstring(\NeoOptimize\NeoGlobal\fs_file_get_contents($file_path)); if ($gd_image === false) { $has_transparency = true; } else { $has_transparency = imagecolortransparent($gd_image) >= 0; } }
                else { $has_transparency = true; }
            } catch (\Throwable $e) { $has_transparency = true; }
        }

        static $warned_about_unknown_imagick_version = false;
        if ($has_transparency && !$imagick_version_found && !$warned_about_unknown_imagick_version) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not determine ImageMagick version for transparent AVIF conversion. Falling back to a safer output format if needed."); $warned_about_unknown_imagick_version = true; }

        if ($preferred_algorithm === "imagick" && $imagick_supports_avif && !$has_transparency)                                                     { return ["imagick", "avif"]; }
        if ($preferred_algorithm === "imagick" && $has_transparency && !$imagick_avif_transparency_safe && !$algorithm_forced && $gd_supports_avif) { return ["gd",      "avif"]; }
        if ($preferred_algorithm === "imagick" && !$imagick_supports_avif && !$algorithm_forced && $gd_supports_avif)                               { return ["gd",      "avif"]; }
        if ($preferred_algorithm === "imagick" && $imagick_supports_webp)                                                                           { return ["imagick", "webp"]; }
        if ($preferred_algorithm === "none") { return ["none", "webp"]; }
        if (!$algorithm_forced && $gd_supports_avif) { return ["gd", "avif"]; }
        if (!$algorithm_forced && $gd_supports_webp) { return ["gd", "webp"]; }
        return ["none", "webp"];
    };

    $file_name_base = optimize_unique_image_filename_prefix($img_url);
    [$resize_algorithm, $webp_or_avif] = $choose_resize_output($image_file_path, $resize_algorithm, $resize_algorithm_forced);
    if (!$is_neodraw && $resize_algorithm === "none") { return; }
    list($cached_width, $cached_height) = $is_neodraw ? [$resized_width, $resized_height] : array_slice($calculate_resize_target_size($orig_img_size[0], $orig_img_size[1], $resized_width, $resized_height), 2, 2);
    $cached_file_name = $file_name_base . intval(round($cached_width)) . "x" . intval(round($cached_height)) . "r" . intval($retina_factor * 100) . "q" . $embedded_webp_quality . $resize_algorithm;
    $cached_file_name .= ($is_neodraw ? ".not-editable.neodraw.min.svg" : "." . $webp_or_avif);
    $cached_file_path = $cache_dir . "/" . $cached_file_name;
    if (!\NeoOptimize\NeoGlobal\fs_file_exists($cached_file_path)) { if (@\NeoOptimize\NeoGlobal\fs_file_put_contents($cached_file_path, "") === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "neoOptimize skipped image because the generated cache file could not be created on the filesystem. Filename: $cached_file_name"); return; } @\NeoOptimize\NeoGlobal\fs_unlink($cached_file_path); }

    if (!(\NeoOptimize\NeoGlobal\fs_file_exists($cached_file_path) && \NeoOptimize\NeoGlobal\fs_filemtime($cached_file_path) === \NeoOptimize\NeoGlobal\fs_filemtime($image_file_path))) {
        if (\NeoOptimize\NeoGlobal\fs_file_exists($cached_file_path) && \NeoOptimize\NeoGlobal\fs_filesize($cached_file_path) === 0 && \NeoOptimize\NeoGlobal\is_cache_file_outdated($cached_file_path, (2) * 60)) { \NeoOptimize\NeoGlobal\fs_unlink($cached_file_path); }
        if (\NeoOptimize\NeoGlobal\fs_file_exists($cached_file_path) && \NeoOptimize\NeoGlobal\fs_filesize($cached_file_path) === 0) { return; }
        if (\NeoOptimize\NeoGlobal\fs_file_exists($cached_file_path)) { \NeoOptimize\NeoGlobal\fs_unlink($cached_file_path); } \NeoOptimize\NeoGlobal\fs_touch($cached_file_path);

        try {
            $resize_image_with_retina_correction = function ($file_path, $output_path, $source_width, $source_height, $target_width, $target_height, $resize_algorithm, $webp_or_avif) use ($calculate_resize_target_size, $embedded_webp_quality, $resize_timeout) {
                try {
                    return \NeoOptimize\NeoGlobal\synclock_dir(\NeoOptimize\NeoGlobal\cache_path("neo-optimize"), function () use (&$file_path, &$output_path, &$source_width, &$source_height, &$target_width, &$target_height, $calculate_resize_target_size, $embedded_webp_quality, $resize_timeout, $resize_algorithm, $webp_or_avif) {
                        list($target_width, $target_height) = $calculate_resize_target_size($source_width, $source_height, $target_width, $target_height);

                        if (!(intval($target_width) > 0 && intval($target_height) > 0)) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Resize target width or height was invalid. Width: $target_width, Height: $target_height, Path: $output_path"); return false; }
                        set_time_limit($resize_timeout); /* phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged */ // (Re-)Set time limit only for this optimization process because resizing images can take a long time #suppressLinterWPorgAutoPhpRuntimeCheck
                        if ($resize_algorithm === "imagick") {
                            $imagick = new \Imagick();
                            try {
                                $imagick->readImage($file_path);
                                $imagick->autoOrient();
                                if (!str_ends_with(strtolower($file_path), ".gif")) {
                                    $imagick->resizeImage($target_width, $target_height, \Imagick::FILTER_LANCZOS, 1);
                                    $imagick->setImageFormat($webp_or_avif);
                                    $imagick->setImageCompressionQuality($embedded_webp_quality);
                                    $webp_or_avif_creation_success = $imagick->writeImage($output_path);
                                } else {
                                    $imagick = $imagick->coalesceImages();
                                    foreach ($imagick as $frame) {
                                        $frame->setImageFormat($webp_or_avif);
                                        $frame->resizeImage($target_width, $target_height, \Imagick::FILTER_LANCZOS, 1);
                                    }
                                    $imagick->setImageFormat($webp_or_avif);
                                    $imagick->setImageCompressionQuality($embedded_webp_quality);
                                    $webp_or_avif_creation_success = $imagick->writeImages($output_path, true);
                                }
                            } finally { $imagick->clear(); $imagick->destroy(); }
                            if ($webp_or_avif_creation_success === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not create a $webp_or_avif image object for resizing the neoDraw image. Path: $output_path"); return false; }
                        } else if ($resize_algorithm === "gd") {
                            if (!str_ends_with(strtolower($file_path), ".gif")) {
                                $image_obj = imagecreatefromstring(\NeoOptimize\NeoGlobal\fs_file_get_contents($file_path));
                                if ($image_obj === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not create an image object from the neoDraw image. Path: $output_path"); return false; }

                                $exif = @exif_read_data($file_path, "IFD0");
                                $orient = 1; if (isset($exif["Orientation"])) { $orient = $exif["Orientation"]; } else if (isset($exif["IFD0"]["Orientation"])) { $orient = $exif["IFD0"]["Orientation"]; }
                                switch ($orient) {
                                    case 1: break;
                                    case 2: imageflip($image_obj, IMG_FLIP_HORIZONTAL); break;
                                    case 3: $image_obj = imagerotate($image_obj, 180, 0); break;
                                    case 4: imageflip($image_obj, IMG_FLIP_VERTICAL); break;
                                    case 5: $image_obj = imagerotate($image_obj, 90, 0); imageflip($image_obj, IMG_FLIP_HORIZONTAL); break;
                                    case 6: $image_obj = imagerotate($image_obj, -90, 0); break;
                                    case 7: $image_obj = imagerotate($image_obj, -90, 0); imageflip($image_obj, IMG_FLIP_HORIZONTAL); break;
                                    case 8: $image_obj = imagerotate($image_obj,  90, 0); break;
                                }
                                $source_width  = imagesx($image_obj); $source_height = imagesy($image_obj);

                                $resized_image_obj = imagecreatetruecolor($target_width, $target_height);
                                if ($resized_image_obj === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not create a new image object for resizing the neoDraw image. Path: $output_path"); return false; }
                                imagealphablending($resized_image_obj, false);
                                imagesavealpha($resized_image_obj, true);
                                imagefill($resized_image_obj, 0, 0, imagecolorallocatealpha($resized_image_obj, 0, 0, 0, 127));
                                imagecopyresampled($resized_image_obj, $image_obj, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height);
                                $webp_or_avif_creation_success = $webp_or_avif === "avif" ? imageavif($resized_image_obj, $output_path, $embedded_webp_quality) : imagewebp($resized_image_obj, $output_path, $embedded_webp_quality);
                                if ($webp_or_avif_creation_success === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not create a $webp_or_avif image object for resizing the neoDraw image. Path: $output_path"); return false; }
                            } else {
                                return false;
                            }
                        }
                        return true;
                    }, $resize_timeout);
                } catch (\Throwable $e) {
                    if (!str_contains($e->getMessage(), "memory allocation failed")) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not resize image. Path: $output_path, Error: " . $e->getMessage()); }
                    return false;
                }
            };

            if (!$is_neodraw) {
                if (!($resize_algorithm === "imagick" || $resize_algorithm === "gd")) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not resize the raster image because neither GD nor Imagick is available (or GD was forced but not available)."); return; }
                $resize_success = $resize_image_with_retina_correction($image_file_path, $cached_file_path, $orig_img_size[0], $orig_img_size[1], $resized_width, $resized_height, $resize_algorithm, $webp_or_avif);
                if ($resize_success === false) { return; }
            } else {
                $svg_content = \NeoOptimize\NeoGlobal\fs_file_get_contents($image_file_path);
                $neo_draw_cached_comment = "<!-- Created with neoDraw - Cached -->";
                if (str_contains($svg_content, $neo_draw_cached_comment)) { return; }

                $image_title = ($image_id === null ? "" : get_post_field("post_title", $image_id, context: "raw"));
                if (\NeoOptimize\NeoGlobal\preg_match_better("/^neodraw([- ][0-9]+)?$/i", $image_title) === 1) { $image_title = ""; }
                $image_title = \NeoOptimize\NeoGlobal\preg_replace_better("/-/", " ", $image_title);
                $image_title = \NeoOptimize\NeoGlobal\preg_replace_better("/(\s|\n|\t)+/", " ", $image_title);

                $content_text = "";
                $text_content_regex = "/<text.*?>(.*?)<\/text>/i";
                \NeoOptimize\NeoGlobal\preg_match_all_better($text_content_regex, $svg_content, $text_content_matches);
                foreach ($text_content_matches[1] as $text_content_match) { $content_text .= $text_content_match . " "; }
                $content_text = trim($content_text);
                $content_text = \NeoOptimize\NeoGlobal\preg_replace_better("/(\s|\n|\t)+/", " ", $content_text);
                    if ($content_text === "") { $neodraw_generated_alt = $image_title; }
                else if ($image_title  === "") { $neodraw_generated_alt = $content_text; }
                else                           { $neodraw_generated_alt = $image_title . " - " . $content_text; }
                if (mb_strlen($neodraw_generated_alt) > 125) { $neodraw_generated_alt = mb_substr($neodraw_generated_alt, 0, 125 - 3) . "..."; }

                $minified_svg_content = $svg_content;

                $excalidraw_start_index =                                                                                  strpos($minified_svg_content, "<!-- START - Excalidraw data -->");
                $excalidraw_end_index   = !str_contains($minified_svg_content, "<!-- END - Excalidraw data -->") ? false : strpos($minified_svg_content, "<!-- END - Excalidraw data -->") + strlen("<!-- END - Excalidraw data -->");
                if ($excalidraw_start_index === false || $excalidraw_end_index === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not minify a neoDraw image (did not find Excalidraw data)."); return; }
                $minified_svg_content = substr_replace($minified_svg_content, "", $excalidraw_start_index, $excalidraw_end_index - $excalidraw_start_index);

                $neo_draw_metadata_start_index =                                                                                   strpos($minified_svg_content, "<!-- START - neoDraw metadata -->");
                $neo_draw_metadata_end_index   = !str_contains($minified_svg_content, "<!-- END - neoDraw metadata -->") ? false : strpos($minified_svg_content, "<!-- END - neoDraw metadata -->") + strlen("<!-- END - neoDraw metadata -->");
                if ($neo_draw_metadata_start_index === false || $neo_draw_metadata_end_index === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not minify a neoDraw image (did not find neoDraw metadata)."); return; }
                $neo_draw_metadata_string = substr($minified_svg_content, $neo_draw_metadata_start_index, $neo_draw_metadata_end_index - $neo_draw_metadata_start_index);

                $pixel_variant_start_index =                                                                                strpos($minified_svg_content, "<!-- START - Pixel variant -->");
                $pixel_variant_end_index   = !str_contains($minified_svg_content, "<!-- END - Pixel variant -->") ? false : strpos($minified_svg_content, "<!-- END - Pixel variant -->") + strlen("<!-- END - Pixel variant -->");
                if ($pixel_variant_start_index === false || $pixel_variant_end_index === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not minify a neoDraw image (did not find Pixel variant data). Path: $image_file_path"); return; }
                $minified_svg_content = substr_replace($minified_svg_content, "", $pixel_variant_start_index, $pixel_variant_end_index - $pixel_variant_start_index);

                $minified_svg_content = \NeoOptimize\NeoGlobal\preg_replace_better("/\n? *<!--(.|\s)*?-->/", "", $minified_svg_content);
                if ($minified_svg_content === null) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not minify a neoDraw image (failed to remove comments)."); return; }

                $minified_svg_content = \NeoOptimize\NeoGlobal\preg_replace_better("/(<svg.*?>)/", "$1\n" .
                    "$neo_draw_cached_comment\n" .
                    "<!-- This minified file cannot be edited with neoDraw! -->\n" .
                    $neo_draw_metadata_string . "\n" .
                    "<!-- Description generated by neoOptimize --><desc>" . esc_html($neodraw_generated_alt) . "</desc>",
                    $minified_svg_content, 1);
                if (\NeoOptimize\NeoGlobal\get_image_metadata($minified_svg_content) === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Broken neoDraw metadata after neoOptimize."); return; }

                $symbol_tag_attributes = [];
                $use_tag_attributes    = [];
                $get_string_between = function ($start_substring, $end_substring_after, $in_string) {
                    $start_index = strpos($in_string, $start_substring);
                    $end_index   = strpos($in_string, $end_substring_after, $start_index + strlen($start_substring));
                    return substr($in_string, $start_index + strlen($start_substring), $end_index - $start_index - strlen($start_substring));
                };
                $search_offset = 0;
                while (true) {
                    $symbol_index = strpos($minified_svg_content, "<symbol ", $search_offset);
                    $use_index = strpos($minified_svg_content, "<use ", $search_offset);
                    if ($symbol_index === false && $use_index === false) { break; }
                    $tag_index = $symbol_index === false ? $use_index : ($use_index === false ? $symbol_index : min($symbol_index, $use_index));
                    if ($tag_index === $symbol_index) {
                        $tag_end_index = strpos($minified_svg_content, "</symbol>", $tag_index);
                        if ($tag_end_index === false) { break; }
                        $tag_string = substr($minified_svg_content, $tag_index, $tag_end_index - $tag_index + strlen("</symbol>"));
                        $symbol_tag_attributes[] = [
                            "id"     => $get_string_between("id=\"", "\"", $tag_string),
                            "mime"   => $get_string_between("data:image/", ";", $tag_string),
                            "base64" => $get_string_between("base64,", "\"", $tag_string),
                        ];
                        $search_offset = $tag_end_index + strlen("</symbol>");
                    } else {
                        $tag_end_index = strpos($minified_svg_content, ">", $tag_index);
                        if ($tag_end_index === false) { break; }
                        $tag_string = substr($minified_svg_content, $tag_index, $tag_end_index - $tag_index + 1);
                        $use_tag_attributes[] = [
                            "id"     => $get_string_between("href=\"#", "\"", $tag_string),
                            "width"  => intval($get_string_between("width=\"", "\"", $tag_string)),
                            "height" => intval($get_string_between("height=\"", "\"", $tag_string)),
                        ];
                        $search_offset = $tag_end_index + 1;
                    }
                }
                foreach ($symbol_tag_attributes as $symbol_attrs) {
                    if (!in_array($symbol_attrs["mime"], explode("|", "jpg|jpeg|jfif|webp|avif|png|gif|bmp|tif|tiff|heic|heif|svg|ico"))) { continue; }

                    if (!($resize_algorithm === "imagick" || $resize_algorithm === "gd")) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not resize the embedded image because neither GD nor Imagick is available (or GD was forced but not available)."); break; }

                    $use_width = 0; $use_height = 0;
                    foreach ($use_tag_attributes as $use_attrs) {
                        if (!($use_attrs["id"] === $symbol_attrs["id"])) { continue; }
                        if ($use_attrs["width"] > $use_width || $use_attrs["height"] > $use_height) { $use_width = $use_attrs["width"]; $use_height = $use_attrs["height"]; }
                    }
                    if (!($use_width > 0 && $use_height > 0)) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not resize the embedded image because no matching SVG use tag with a valid size was found. Symbol ID: " . $symbol_attrs["id"]); continue; }

                    $temp_file_data_path = $cached_file_path . "-" . substr($symbol_attrs["id"], -8) . ".original.temp-neodraw-embedded." . $symbol_attrs["mime"];
                    $embedded_image_data = base64_decode($symbol_attrs["base64"]);
                    if ($embedded_image_data === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not read the base64 data of the embedded image from the neoDraw SVG."); continue; }
                    \NeoOptimize\NeoGlobal\fs_file_put_contents($temp_file_data_path, $embedded_image_data);
                    $original_size = $get_image_size($temp_file_data_path);
                    if (!(is_array($original_size) && ($original_size[0] ?? 0) > 0 && ($original_size[1] ?? 0) > 0)) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not read the width and height of the embedded image from the neoDraw SVG."); \NeoOptimize\NeoGlobal\fs_unlink($temp_file_data_path); continue; }
                    list($original_width, $original_height) = $original_size;
                    list($use_width, $use_height) = $object_fit_cover_size($use_width, $use_height, $original_width / $original_height);

                    $use_width_on_screen  = $resized_width  * $use_width  / $svg_meta["width"];
                    $use_height_on_screen = $resized_height * $use_height / $svg_meta["height"];
                    if (!($use_width_on_screen > 0 && $use_height_on_screen > 0)) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not resize the embedded image because the calculated screen size is invalid. Width: $use_width_on_screen, Height: $use_height_on_screen, Symbol ID: " . $symbol_attrs["id"]); \NeoOptimize\NeoGlobal\fs_unlink($temp_file_data_path); continue; }

                    [$embedded_resize_algorithm, $embedded_webp_or_avif] = $choose_resize_output($temp_file_data_path, $resize_algorithm, $resize_algorithm_forced);
                    if ($embedded_resize_algorithm === "none") { \NeoOptimize\NeoGlobal\fs_unlink($temp_file_data_path); continue; }
                    $webp_out_path = str_replace("original", "resized", $temp_file_data_path) . "." . $embedded_webp_or_avif;
                    $resize_success = $resize_image_with_retina_correction($temp_file_data_path, $webp_out_path, $original_width, $original_height, $use_width_on_screen, $use_height_on_screen, $embedded_resize_algorithm, $embedded_webp_or_avif);
                    \NeoOptimize\NeoGlobal\fs_unlink($temp_file_data_path);
                    if (!$resize_success) { continue; }

                    $resized_webp_image_buffer_string = \NeoOptimize\NeoGlobal\fs_file_get_contents($webp_out_path);
                    if ($resized_webp_image_buffer_string === false) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not read the $embedded_webp_or_avif image from the neoDraw temp file."); continue; }

                    $resized_image_base64_data = base64_encode($resized_webp_image_buffer_string);
                    $minified_svg_content = str_replace("data:image/" . $symbol_attrs["mime"] . ";base64," . $symbol_attrs["base64"], "data:image/" . $embedded_webp_or_avif . ";base64," . $resized_image_base64_data, $minified_svg_content);
                }

                $write_success = \NeoOptimize\NeoGlobal\fs_file_put_contents($cached_file_path, $minified_svg_content);
                if (!$write_success) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not save the minified SVG image to the neoDraw cache directory."); return; }
            }

            \NeoOptimize\NeoGlobal\fs_touch($cached_file_path, \NeoOptimize\NeoGlobal\fs_filemtime($image_file_path));

            \NeoOptimize\NeoGlobal\global_log_with_module_name("neo-optimize", "New optimized filename: $cached_file_name (deduplicated)");
        } finally {
            if (\NeoOptimize\NeoGlobal\fs_file_exists($cached_file_path) && \NeoOptimize\NeoGlobal\fs_filesize($cached_file_path) === 0) { \NeoOptimize\NeoGlobal\fs_unlink($cached_file_path); }
        }
    }

    $set_attr("src", \NeoOptimize\NeoGlobal\cache_url("neo-optimize") . "/" . $cached_file_name);
    $original_query_params = wp_parse_url($img_url, PHP_URL_QUERY);
    if ($original_query_params !== null) { $set_attr("src", $get_attr("src") . "?" . $original_query_params); }
    $set_attr("src", \NeoOptimize\NeoGlobal\add_query_param($get_attr("src"), "neo-optimize--id", strval($image_id ?? -1)));
    $set_attr("src", \NeoOptimize\NeoGlobal\add_query_param($get_attr("src"), "compression", $webp_or_avif));
    $set_attr("data-neo-optimize--original-url", \NeoOptimize\NeoGlobal\remove_all_query_params($img_url));

    if ($is_neodraw) {
        $neodraw_svg_file_content_first_4_kb = \NeoOptimize\NeoGlobal\fs_file_get_contents($cached_file_path, false, null, 0, 4096);
        \NeoOptimize\NeoGlobal\preg_match_all_better("/<desc>(.*?)<\/desc>/i", $neodraw_svg_file_content_first_4_kb, $alt_text_matches);
        if (count($alt_text_matches[1]) > 0) {
            $neodraw_generated_alt = $alt_text_matches[1][0];
            $neodraw_generated_alt = html_entity_decode($neodraw_generated_alt, ENT_QUOTES, "UTF-8");

            $image_title = ($image_id === null ? "" : get_post_field("post_title", $image_id, context: "raw"));
            $image_filename = basename($img_url);
            $filter_a_to_z = function ($alt_text) { return \NeoOptimize\NeoGlobal\preg_replace_better("/[^a-zA-Z]/", "", $alt_text); };
            if (($get_attr("alt") ?? "") === "" || strtolower($filter_a_to_z($get_attr("alt"))) === strtolower($filter_a_to_z($image_title)) || strtolower($filter_a_to_z($get_attr("alt"))) === strtolower($filter_a_to_z($image_filename))) {
                $set_attr("alt", $neodraw_generated_alt);
            }
        }
    }
}

\NeoOptimize\NeoGlobal\add_action_hook("init:10", function () { \NeoOptimize\NeoGlobal\frontend_image_hook_register_callback(function ($get_attr, $set_attr, $img_path) { optimize_modify_frontend_img($get_attr, $set_attr, $img_path); }); });

\NeoOptimize\NeoGlobal\add_filter_hook("bricks/element/render_attributes", function ($attributes, $key, $element) {
    if ($element->block !== "core/image") { return $attributes; }

    $max_width_setting  = ($element->settings["_widthMax"]  ?? null) ?: null;
    $max_height_setting = ($element->settings["_heightMax"] ?? null) ?: null;
    $width_setting      = ($element->settings["_width"]     ?? null) ?: null;
    $height_setting     = ($element->settings["_height"]    ?? null) ?: null;
    $max_set = ($max_width_setting !== null || $max_height_setting !== null);
    $width  = $max_set ? $max_width_setting  : $width_setting;
    $height = $max_set ? $max_height_setting : $height_setting;
    if (is_numeric($width))  { $width  = $width  . "px"; }
    if (is_numeric($height)) { $height = $height . "px"; }
    if (!(isset($attributes[$key]["data-neo-optimize--width"]) || isset($attributes[$key]["data-neo-optimize--height"]))) {
        if ($width  !== null)                    { $attributes[$key]["data-neo-optimize--width"]  = $width; }
        if ($height !== null)                    { $attributes[$key]["data-neo-optimize--height"] = $height; }
        if ($width === null && $height === null) { $attributes[$key]["data-neo-optimize--width"]  = "100%"; }
    }
    return $attributes;
});

\NeoOptimize\NeoGlobal\add_filter_hook("elementor/widget/render_content", function ($widget_content, $widget) {
    if (!in_array($widget->get_name(), ["image", "e-image"])) { return $widget_content; }

    \NeoOptimize\NeoGlobal\preg_match_all_better('/\b([a-zA-Z0-9-]+)\s*=/i', $widget_content, $existing_attributes); $existing_attributes = array_flip($existing_attributes[1]);

    if ($widget->get_name() === "image") {
        $size_data_attributes = "";
        $max_width_setting = ($widget->get_settings()["space"]["size"]  ?? "") !== "" ? ($widget->get_settings()["space"]["size"]  . $widget->get_settings()["space"]["unit"])  : null;
        $width_setting     = ($widget->get_settings()["width"]["size"]  ?? "") !== "" ? ($widget->get_settings()["width"]["size"]  . $widget->get_settings()["width"]["unit"])  : null;
        $height_setting    = ($widget->get_settings()["height"]["size"] ?? "") !== "" ? ($widget->get_settings()["height"]["size"] . $widget->get_settings()["height"]["unit"]) : null;
        $max_set = ($max_width_setting !== null);
    } else {
        $size_data_attributes = "";
        $new_style_settings = ["max-width" => null, "width" => null, "height" => null];
        $widget_styles = method_exists($widget, "get_raw_data") ? ($widget->get_raw_data()["styles"] ?? []) : [];
        foreach ($widget_styles as $style_id => $style) {
            foreach (($style["variants"] ?? []) as $variant) {
                if (($variant["meta"]["breakpoint"] ?? "desktop") !== "desktop" || ($variant["meta"]["state"] ?? null) !== null) { continue; }
                foreach ($new_style_settings as $style_prop_name => $style_prop_setting) {
                    if ($style_prop_setting !== null || !isset($variant["props"][$style_prop_name]["value"])) { continue; }
                    $style_prop_value = $variant["props"][$style_prop_name]["value"];
                    if (is_string($style_prop_value)) { $new_style_settings[$style_prop_name] = $style_prop_value; continue; }
                    if (($style_prop_value["size"] ?? "") !== "") { $new_style_settings[$style_prop_name] = $style_prop_value["size"] . ($style_prop_value["unit"] ?? "px"); }
                }
            }
        }
        $max_width_setting = $new_style_settings["max-width"];
        $width_setting = $new_style_settings["width"];
        $height_setting = $new_style_settings["height"];
        $max_set = ($max_width_setting !== null);
    }
    $width  = $max_set ? $max_width_setting : $width_setting;
    $height = $max_set ? $max_width_setting : $height_setting;
    if (!(isset($existing_attributes["data-neo-optimize--width"]) || isset($existing_attributes["data-neo-optimize--height"]))) {
        if ($width  !== null)                     { $size_data_attributes .= ' data-neo-optimize--width="'  . $width  . '"'; }
        if ($height !== null)                     { $size_data_attributes .= ' data-neo-optimize--height="' . $height . '"'; }
        if ($width  === null && $height === null) { $size_data_attributes .= ' data-neo-optimize--width="100%"'; }
    }

    $widget_content = str_replace("<img", "<img" . $size_data_attributes, $widget_content);
    return $widget_content;
});

\NeoOptimize\NeoGlobal\add_action_hook("delete_attachment", function ($post_id) {
    $image_file_url = wp_get_attachment_url($post_id);
    if ($image_file_url === false) { return; }
    $image_base_file_name = optimize_unique_image_filename_prefix($image_file_url);

    foreach (new \DirectoryIterator(\NeoOptimize\NeoGlobal\cache_path("neo-optimize")) as $file) {
        if (!$file->isFile()) { continue; }
        $file_name = $file->getFilename();
        if (str_starts_with($file_name, $image_base_file_name)) {
            $delete_success = \NeoOptimize\NeoGlobal\fs_unlink($file->getPathname());
            if (!$delete_success) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not delete the cached SVG image " . $file_name); continue; }
        }
    }
});

function interface_list_cached_images_20250302($image_url) {
    static $neo_optimize_cache_path = null; $neo_optimize_cache_path ??= \NeoOptimize\NeoGlobal\cache_path("neo-optimize"); static $neo_optimize_cache_url = null; $neo_optimize_cache_url ??= \NeoOptimize\NeoGlobal\cache_url("neo-optimize");
    if (!\NeoOptimize\NeoGlobal\fs_is_readable($neo_optimize_cache_path)) { return []; }
    $cached_images = [];
    $prefix = optimize_unique_image_filename_prefix($image_url);
    static $cached_file_names_by_prefix = null; if ($cached_file_names_by_prefix === null) { $cached_file_names_by_prefix = [];
        foreach (\NeoOptimize\NeoGlobal\fs_scandir($neo_optimize_cache_path) as $file_name) {
            if (str_contains($file_name, ".temp-neodraw-embedded.")) { continue; }
            $file_prefix_end_pos = strpos($file_name, "~~"); if ($file_prefix_end_pos === false) { continue; }
            $cached_file_names_by_prefix[substr($file_name, 0, $file_prefix_end_pos + 2)][] = $file_name;
        }
    }
    foreach (($cached_file_names_by_prefix[$prefix] ?? []) as $cached_file_name) {
        $cached_images["$neo_optimize_cache_url/$cached_file_name"] = "$neo_optimize_cache_path/$cached_file_name";
    }
    uasort($cached_images, function($a, $b) { return \NeoOptimize\NeoGlobal\fs_filesize($a) <=> \NeoOptimize\NeoGlobal\fs_filesize($b); });
    return $cached_images;
}

function resize_img($image_file_path_or_url, $width, $height, $retina = null, $quality = null, $algorithm = null, $output_format = null) {
    $wp_path = ABSPATH;       if (str_ends_with($wp_path, "/")) { $wp_path = substr($wp_path, 0, -1); }/* Using ABSPATH is unavoidable because any image in the WP instance should resolve to its path. If there are any deviations in the WP setup, this function is robust against that and downloads the image into the local cache folder #suppressLinterWporgDirectoryConstantCheck */
    $wp_url = get_site_url(); if (str_ends_with($wp_url, "/"))  { $wp_url = substr($wp_url,   0, -1); }
         if (\NeoOptimize\NeoGlobal\is_url_absolute($image_file_path_or_url) && \NeoOptimize\NeoGlobal\is_url_internal($image_file_path_or_url)) { $image_url  = $image_file_path_or_url; $image_path = \NeoOptimize\NeoGlobal\get_path_from_internal_url($image_file_path_or_url); }
    else if (str_starts_with($image_file_path_or_url, $wp_path))                                                         { $image_path = $image_file_path_or_url; $image_url  = str_replace($wp_path, $wp_url, $image_file_path_or_url); }
    else {
        $file_contents = str_starts_with($image_file_path_or_url, "/") ? \NeoOptimize\NeoGlobal\fs_file_get_contents($image_file_path_or_url) : \NeoOptimize\NeoGlobal\curl_request($image_file_path_or_url);
        return \NeoOptimize\NeoGlobal\synclock_dir(\NeoOptimize\NeoGlobal\cache_path("neo-optimize--temp-download"), function () use (&$image_file_path_or_url, &$file_contents, &$width, &$height, &$retina, &$quality, &$algorithm, &$output_format) {
            try {
                $neo_optimize_image_file_path = \NeoOptimize\NeoGlobal\cache_path("neo-optimize--temp-download") . "/" . \NeoOptimize\NeoGlobal\preg_replace_better('/[^a-zA-Z0-9_\-\.]/', "_", $image_file_path_or_url);
                if ($file_contents === false) { \NeoOptimize\NeoGlobal\throw_global_exception("Could not read the image file from path: " . $image_file_path_or_url); }
                \NeoOptimize\NeoGlobal\fs_file_put_contents($neo_optimize_image_file_path, $file_contents);
                $optimized_img_url = resize_img($neo_optimize_image_file_path, $width, $height, $retina, $quality, $algorithm, $output_format);
                if (!str_contains($optimized_img_url, "/neo-optimize/")) {
                    $optimize_cache_filename = pathinfo(basename($optimized_img_url), PATHINFO_FILENAME) . "-not-optimized." . pathinfo(basename($optimized_img_url), PATHINFO_EXTENSION);
                    \NeoOptimize\NeoGlobal\fs_copy($neo_optimize_image_file_path, \NeoOptimize\NeoGlobal\cache_path("neo-optimize") . "/" . $optimize_cache_filename);
                    return \NeoOptimize\NeoGlobal\cache_url("neo-optimize") . "/" . $optimize_cache_filename;
                }
                return $optimized_img_url;
            } finally {
                \NeoOptimize\NeoGlobal\delete_all(\NeoOptimize\NeoGlobal\cache_path("neo-optimize--temp-download") . "/*");
            }
        });
    }

    $get_attr = function ($attr) use (&$image_url, &$width, &$height, &$retina, &$quality, &$algorithm) {
        if ($attr === "src")    { return $image_url; }
        if ($attr === "width")  { return $width; }
        if ($attr === "height") { return $height; }
        if ($attr === "data-neo-optimize--retina")    { return $retina; }
        if ($attr === "data-neo-optimize--quality")   { return $quality; }
        if ($attr === "data-neo-optimize--algorithm") { return $algorithm; }
        return null;
    };

    $set_attr = function ($attr, $value) use (&$image_url) {
        if ($attr === "src") { $image_url = $value; }
    };

    optimize_modify_frontend_img($get_attr, $set_attr, $image_path, $output_format);
    return $image_url;
}

function interface_resize_img_20251030($image_file_path_or_url, $smallest_size = 1080, $retina = null, $quality = null, $algorithm = null) { return resize_img($image_file_path_or_url, $smallest_size, $smallest_size, $retina, $quality, $algorithm); }

function interface_get_original_image_url_20251030($cached_image_url) {
    $image_id = \NeoOptimize\NeoGlobal\get_query_param_from_url($cached_image_url, "neo-optimize--id");
    if ($image_id === null) { return null; }
    $image_id = intval($image_id); if ($image_id <= 0) { return null; }
    return wp_get_attachment_url($image_id) ?: null;
}

function interface_rename_image_20251030($img_url_before, $img_url_after) {
    $cached_images = interface_list_cached_images_20250302($img_url_before);
    $cache_prefix_before = optimize_unique_image_filename_prefix($img_url_before);
    $cache_prefix_after  = optimize_unique_image_filename_prefix($img_url_after);
    foreach ($cached_images as $cached_image_url => $cached_image_path) {
        if (!(str_starts_with($cached_image_url, \NeoOptimize\NeoGlobal\cache_url("neo-optimize") . "/" . $cache_prefix_before))) { continue; }
        $new_cached_image_path = \NeoOptimize\NeoGlobal\str_replace_start(\NeoOptimize\NeoGlobal\cache_path("neo-optimize") . "/" . $cache_prefix_before, \NeoOptimize\NeoGlobal\cache_path("neo-optimize") . "/" . $cache_prefix_after, $cached_image_path);
        $rename_success = \NeoOptimize\NeoGlobal\fs_rename($cached_image_path, $new_cached_image_path);
        if (!$rename_success) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-optimize", "Could not rename the cached SVG image $cached_image_path to $new_cached_image_path"); continue; }
    }
}

function interface_regenerate_image_20251030($img_url) {
    $cached_images = interface_list_cached_images_20250302($img_url);
    $cache_prefix = optimize_unique_image_filename_prefix($img_url);
    foreach ($cached_images as $cached_image_url => $cached_image_path) {
        if (!str_starts_with($cached_image_url, \NeoOptimize\NeoGlobal\cache_url("neo-optimize") . "/" . $cache_prefix)) { continue; }

        \NeoOptimize\NeoGlobal\preg_match_better('/(?P<filename>.*)~~(?P<width>\d*)x(?P<height>\d*)r(?P<retina>\d*)q(?P<quality>\d*)(?P<algorithm>[^.]*).(?P<type>.*)/', basename($cached_image_path), $matches);
        $width     = isset($matches["width"])     ? intval($matches["width"])     : null;
        $height    = isset($matches["height"])    ? intval($matches["height"])    : null;
        $retina    = isset($matches["retina"])    ? intval($matches["retina"])    : null;
        $quality   = isset($matches["quality"])   ? intval($matches["quality"])   : null;
        $algorithm = isset($matches["algorithm"]) ? strval($matches["algorithm"]) : null;

        \NeoOptimize\NeoGlobal\fs_unlink($cached_image_path);
        resize_img($img_url, $width, $height, $retina, $quality, $algorithm);
    }
}

\NeoOptimize\NeoGlobal\add_action_hook("current_screen", function () { \NeoOptimize\NeoGlobal\cache_path("neo-optimize"); });

\NeoOptimize\NeoGlobal\add_action_hook("template_redirect", function () {
    if (!is_404()) { return; }
    $cache_url = \NeoOptimize\NeoGlobal\cache_url("neo-optimize");
    $image_url = \NeoOptimize\NeoGlobal\request_uri();
    if (!(str_starts_with($image_url, $cache_url) || str_starts_with($image_url, \NeoOptimize\NeoGlobal\get_url_path($cache_url)))) { return; }

    $requested_image_path = \NeoOptimize\NeoGlobal\get_url_path($image_url);
    $requested_format = strtolower(pathinfo($requested_image_path, PATHINFO_EXTENSION));
    if (in_array($requested_format, ["avif", "webp"], true)) {
        $redirect_format = $requested_format === "avif" ? "webp" : "avif";
        $redirect_image_path = substr($requested_image_path, 0, -strlen("." . $requested_format)) . "." . $redirect_format;
        $redirect_image_file_path = \NeoOptimize\NeoGlobal\cache_path("neo-optimize") . "/" . basename($redirect_image_path);
        if (!(\NeoOptimize\NeoGlobal\fs_is_file($redirect_image_file_path) && \NeoOptimize\NeoGlobal\fs_filesize($redirect_image_file_path) > 0)) { $redirect_image_file_path = null; }
        if ($redirect_image_file_path !== null) {
            $redirect_image_url = \NeoOptimize\NeoGlobal\add_or_update_query_params(str_replace($requested_image_path, $redirect_image_path, $image_url), ["compression" => $redirect_format]);
            \NeoOptimize\NeoGlobal\global_log_with_module_name("neo-optimize", "neoOptimize redirected missing $requested_format cache image to existing $redirect_format cache image: $redirect_image_url (deduplicated)");
            wp_safe_redirect($redirect_image_url, 302); exit;
        }
    }

    $original_image_url = interface_get_original_image_url_20251030($image_url);
    if ($original_image_url === null) { return; }
    wp_safe_redirect(\NeoOptimize\NeoGlobal\add_query_param($original_image_url, "neo-optimize--hint", "clear-website-cache-to-get-the-optimized-version-of-this-image"), 302); exit;
});

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoReset\interface_register_neo_reset_action_20260410')(
        id: "neo-optimize--clear-cache", button_text: \NeoOptimize\NeoGlobal\neo__("Clear neoOptimize cache", "neoOptimize-Cache leeren"), confirm_title: \NeoOptimize\NeoGlobal\neo__("Clear neoOptimize cache?", "neoOptimize-Cache leeren?"), confirm_text: \NeoOptimize\NeoGlobal\neo__("Are you sure you want to delete all optimized image cache files?", "Möchtest du wirklich alle optimierten Bild-Cache-Dateien löschen?"),
        action_callback: fn () => \NeoOptimize\NeoGlobal\delete_all(\NeoOptimize\NeoGlobal\cache_path("neo-optimize"))
    );
});
