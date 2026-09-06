<?php
namespace NeoOptimize\NeoOptimize; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoOptimize\NeoGlobal\register_rest_endpoint("/wp-json/neo/optimize-clear-cache", "POST", fn () => \NeoOptimize\NeoGlobal\current_user_can__neo_optimize__settings(), function () {
    \NeoOptimize\NeoGlobal\delete_all(\NeoOptimize\NeoGlobal\cache_path("neo-optimize"));
    return ["success" => true];
});

function optimize_preview_setting_choices() {
    $choices = ["output_formats" => ["webp", "avif"], "default_output_format" => "avif", "qualities" => [10, 40, 75, 85, 95], "default_quality" => 75];

    $choices["retina_factors"] = [100, 125, 150, 175, 200]; $choices["default_retina_factor"] = 150;

    return $choices;
}

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoOptimize\NeoGlobal\register_rest_endpoint("/wp-json/neo/optimize-preview-settings", "POST", fn () => \NeoOptimize\NeoGlobal\current_user_can__neo_optimize__settings(), function ($get_param) {
        $selected_output_format = $get_param("output-format");
        if (!in_array($selected_output_format, optimize_preview_setting_choices()["output_formats"], true)) { \NeoOptimize\NeoGlobal\throw_global_exception("Invalid neoOptimize preview output format."); }
        if (!optimize_format_support($selected_output_format)) { $selected_output_format = $selected_output_format === "avif" ? "webp" : "avif"; if (!optimize_format_support($selected_output_format)) { \NeoOptimize\NeoGlobal\throw_global_exception("No supported neoOptimize preview output format is available."); } }
        $selected_quality = intval($get_param("quality"));
        if (!in_array($selected_quality, optimize_preview_setting_choices()["qualities"], true)) { \NeoOptimize\NeoGlobal\throw_global_exception("Invalid neoOptimize preview quality."); }
        $selected_retina_factor = null;
        $preload_retina_factors = [null];
        $retina_axis_factors = [];
        $selected_retina_factor = intval($get_param("retina-factor")); if (!in_array($selected_retina_factor, optimize_preview_setting_choices()["retina_factors"], true)) { \NeoOptimize\NeoGlobal\throw_global_exception("Invalid neoOptimize preview retina factor."); } $preload_retina_factors = [optimize_preview_setting_choices()["default_retina_factor"]]; 
        $changed_axis = $get_param("changed-axis");
        if (!in_array($changed_axis, [null, "output-format", "quality", "retina-factor"], true)) { \NeoOptimize\NeoGlobal\throw_global_exception("Invalid neoOptimize preview axis."); }
        $preload_all = $get_param("preload-all") === true;

        $preview_coordinates = [];
        $add_preview_coordinate = function ($output_format, $quality, $retina_factor) use (&$preview_coordinates) {
            if (!optimize_format_support($output_format)) { return; }
            $variant_key = $output_format . "-" . $quality; $coordinate = ["output_format" => $output_format, "quality" => $quality];
            $variant_key .= "-" . $retina_factor; $coordinate["retina_factor"] = $retina_factor;
            $preview_coordinates[$variant_key] = $coordinate;
        };
        if ($preload_all) {
            foreach (optimize_preview_setting_choices()["output_formats"] as $output_format) { foreach (optimize_preview_setting_choices()["qualities"] as $quality) { foreach ($preload_retina_factors as $retina_factor) { $add_preview_coordinate($output_format, $quality, $retina_factor); } } }
        } else {
            $add_preview_coordinate($selected_output_format, $selected_quality, $selected_retina_factor);
            if ($changed_axis !== "output-format") { foreach (optimize_preview_setting_choices()["output_formats"] as $output_format) { $add_preview_coordinate($output_format, $selected_quality, $selected_retina_factor); } }
            if ($changed_axis !== "quality") { foreach (optimize_preview_setting_choices()["qualities"] as $quality) { $add_preview_coordinate($selected_output_format, $quality, $selected_retina_factor); } }
            if ($changed_axis !== "retina-factor") { foreach ($retina_axis_factors as $retina_factor) { $add_preview_coordinate($selected_output_format, $selected_quality, $retina_factor); } }
        }

        $source_file_path = \NeoOptimize\NeoGlobal\plugin_path() . "/img/neo-optimize--example-image.jpg";
        $engine_from_url = function ($image_url) { \NeoOptimize\NeoGlobal\preg_match_better("/q[0-9]{1,3}(gd|imagick|none)/", basename(\NeoOptimize\NeoGlobal\remove_all_query_params($image_url)), $matches); return ($matches[1] ?? "") === "gd" ? "php-gd" : ($matches[1] ?? "unknown"); };
        $format_from_url = function ($image_url) { return strtoupper(pathinfo(\NeoOptimize\NeoGlobal\remove_all_query_params($image_url), PATHINFO_EXTENSION)); };
        $example_cache_path = \NeoOptimize\NeoGlobal\cache_path("neo-optimize--example-image");
        return \NeoOptimize\NeoGlobal\synclock_dir($example_cache_path, timeout: 210, callback: function () use ($source_file_path, $engine_from_url, $format_from_url, $example_cache_path, $preview_coordinates) {
            $example_source_file_path = $example_cache_path . "/neo-optimize--example-image-source.jpg"; if (!\NeoOptimize\NeoGlobal\fs_copy($source_file_path, $example_source_file_path) || !\NeoOptimize\NeoGlobal\fs_touch($example_source_file_path, \NeoOptimize\NeoGlobal\fs_filemtime($source_file_path))) { \NeoOptimize\NeoGlobal\throw_global_exception("Could not prepare neoOptimize example image source."); }
            $temp_base_file_paths = [];
            try {
                $max_retina_factor = null;
                $max_retina_factor = max(optimize_preview_setting_choices()["retina_factors"]);
                $variants = [];
                foreach (optimize_preview_setting_choices()["output_formats"] as $output_format) {
                    if (!optimize_format_support($output_format)) { continue; }
                    $max_img_url = resize_img($example_source_file_path, 800, 800, $max_retina_factor, max(optimize_preview_setting_choices()["qualities"]), output_format: $output_format);
                    $max_img_path = \NeoOptimize\NeoGlobal\get_path_from_internal_url($max_img_url);
                    if ($max_img_path === $example_source_file_path || !\NeoOptimize\NeoGlobal\fs_is_file($max_img_path) || \NeoOptimize\NeoGlobal\fs_filesize($max_img_path) === 0) { \NeoOptimize\NeoGlobal\throw_global_exception("Could not generate neoOptimize example image base variant."); }
                    $temp_base_file_path = $example_cache_path . "/neo-optimize--example-image-source-" . $output_format . "." . strtolower(pathinfo($max_img_path, PATHINFO_EXTENSION));
                    $temp_base_file_paths[] = $temp_base_file_path;
                    \NeoOptimize\NeoGlobal\fs_copy($max_img_path, $temp_base_file_path); \NeoOptimize\NeoGlobal\fs_touch($temp_base_file_path, \NeoOptimize\NeoGlobal\fs_filemtime($source_file_path));
                    foreach ($preview_coordinates as $variant_key => $coordinate) {
                        if ($coordinate["output_format"] !== $output_format) { continue; }
                        $preview_context = $coordinate["retina_factor"] ?? null;
                        $resized_img_url = resize_img($temp_base_file_path, 800, 800, $preview_context, $coordinate["quality"], output_format: $output_format);
                        if (!str_contains($resized_img_url, "/neo-optimize/")) { \NeoOptimize\NeoGlobal\throw_global_exception("Could not generate neoOptimize example image variant."); }
                        $resized_img_path = \NeoOptimize\NeoGlobal\get_path_from_internal_url($resized_img_url);
                        if (!\NeoOptimize\NeoGlobal\fs_is_file($resized_img_path) || \NeoOptimize\NeoGlobal\fs_filesize($resized_img_path) === 0) { \NeoOptimize\NeoGlobal\throw_global_exception("Could not generate neoOptimize example image variant file."); }
                        $variants[$variant_key] = $coordinate + ["url" => $resized_img_url, "size_bytes" => \NeoOptimize\NeoGlobal\fs_filesize($resized_img_path), "engine" => $engine_from_url($resized_img_url), "format" => $format_from_url($resized_img_url)];
                    }
                }
                return ["variants" => $variants, "original_size_bytes" => \NeoOptimize\NeoGlobal\fs_filesize($source_file_path)];
            } finally {
                foreach ($temp_base_file_paths as $temp_base_file_path) {
                    if (\NeoOptimize\NeoGlobal\fs_is_file($temp_base_file_path)) { \NeoOptimize\NeoGlobal\fs_unlink($temp_base_file_path); }
                }
            }
        }, scope: "preview-settings");
    });
    \NeoOptimize\NeoGlobal\register_rest_endpoint("/wp-json/neo/optimize-default-settings", "POST", fn () => \NeoOptimize\NeoGlobal\current_user_can__neo_optimize__settings(), function ($get_param) {
        $output_format = $get_param("output-format");
        if (!in_array($output_format, optimize_preview_setting_choices()["output_formats"], true)) { \NeoOptimize\NeoGlobal\throw_global_exception("Invalid neoOptimize output format."); }
        if (!optimize_format_support($output_format)) { $output_format = $output_format === "avif" ? "webp" : "avif"; if (!optimize_format_support($output_format)) { \NeoOptimize\NeoGlobal\throw_global_exception("No supported neoOptimize output format is available."); } }
        \NeoOptimize\NeoGlobal\option__neo_optimize__avif($output_format === "avif");
        $quality = intval($get_param("quality"));
        \NeoOptimize\NeoGlobal\option__neo_optimize__quality($quality);

        $saved_retina_factor = optimize_preview_setting_choices()["default_retina_factor"];  return ["output_format" => $output_format, "quality" => \NeoOptimize\NeoGlobal\option__neo_optimize__quality(), "retina_factor" => $saved_retina_factor];
        return ["output_format" => $output_format, "quality" => \NeoOptimize\NeoGlobal\option__neo_optimize__quality()];
    });
    $settings_render_callback = function () {
        $webp_supported = optimize_format_support("webp"); $avif_supported = optimize_format_support("avif");
        $selected_output_format = \NeoOptimize\NeoGlobal\option__neo_optimize__avif() ? "avif" : "webp";
        if (!($selected_output_format === "avif" ? $avif_supported : $webp_supported)) { $selected_output_format = $selected_output_format === "avif" ? "webp" : "avif"; }
        $selected_quality = \NeoOptimize\NeoGlobal\option__neo_optimize__quality();
        $selected_retina_factor = optimize_preview_setting_choices()["default_retina_factor"]; 
        ?><neo-setting-neo-optimize>
            <div slot="left">
                <h3><?php \NeoOptimize\NeoGlobal\echo_neo__("Optimization settings", "Optimierungseinstellungen"); ?><neo-info-tooltip-neo-optimize style="margin-left: 6px;"><?php \NeoOptimize\NeoGlobal\echo_neo__("For instructions on changing image settings individually per image, see the neoRename guide:", "Für eine Anleitung zum Umstellen der Bildeinstellungen individuell pro Bild siehe die neoRename-Anleitung:"); ?> <a href="<?php echo esc_url("https://" . \NeoOptimize\NeoGlobal\option__neo_wp_com() . \NeoOptimize\NeoGlobal\neo__("", "/de") . "/plugin/neo-optimize/?ref=neo-optimize--settings#attributes"); ?>" target="_blank" rel="noopener noreferrer"><?php \NeoOptimize\NeoGlobal\echo_neo__("Open guide", "Anleitung öffnen"); ?></a></neo-info-tooltip-neo-optimize></h3>
                <p><?php \NeoOptimize\NeoGlobal\echo_neo__("Images are processed directly on the server itself. An optimized copy is delivered from the cache. The originals remain untouched.", "Bilder werden direkt auf dem eigenen Server verarbeitet. Ausgespielt wird eine optimierte Kopie aus dem Cache. Die Originale bleiben unberührt."); ?></p>
            </div>
            <div id="neo-optimize--default-settings-root" data-selected-output-format="<?php echo esc_attr($selected_output_format); ?>" data-selected-quality="<?php echo esc_attr($selected_quality); ?>" data-selected-retina-factor="<?php echo esc_attr($selected_retina_factor); ?>" data-preview-loading="true">
                <?php \NeoOptimize\NeoGlobal\backend_page_style_tag_start([]); ?>
                    #neo-optimize--default-settings-root { display: flex; flex-direction: column; gap: 16px; }
                    #neo-optimize--default-settings-root .neo-optimize--setting-label-text { display: inline-flex; align-items: center; gap: 5px; font-weight: 700; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-group { display: flex; flex-direction: column; gap: 3px; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-list { display: flex; flex-wrap: wrap; gap: 8px; padding-top: 12px; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-button { position: relative; display: flex; flex: 1 1 calc((100% - 32px) / 5); min-width: 0; min-height: 78px; max-width: 50%; overflow: visible; flex-direction: column; align-items: flex-start; justify-content: center; gap: 5px; padding: 10px; border: 1px solid #dcdcde; border-radius: 8px; background: #fff; color: #1d2327; cursor: pointer; text-align: left; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04); }
                    #neo-optimize--default-settings-root [data-choice-group="output-format"] .neo-optimize--choice-button { flex-grow: 0; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-button[aria-pressed="true"] { border-color: #007DBA; background: #2271b1; color: #fff; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-button:disabled { cursor: not-allowed; opacity: 0.7; }

                    #neo-optimize--default-settings-root .neo-optimize--choice-crown { position: absolute; top: 6px; right: 6px; width: 1em; height: 1em; }

                    #neo-optimize--default-settings-root .neo-optimize--choice-value { font-size: 1.4em; font-weight: 700; line-height: 1; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-size { font-size: 1em; line-height: 1.2; opacity: 0.85; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-saving { color: #16803a; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-default { position: absolute; top: 1px; right: -1px; z-index: 1; transform: translateY(-66%); padding: 3px 8px 2px; border: inherit; border-radius: 6px; background-color: inherit; color: inherit; box-shadow: 0 0px 2px rgba(0, 0, 0, 0.04); font-size: 0.85em; font-weight: 700; line-height: 1.3; }
                    #neo-optimize--default-settings-root .neo-optimize--format-unsupported-message { margin: 0; color: #b32d2e; }
                    #neo-optimize--default-settings-root .neo-optimize--avif-cache-hint { margin: 0; }
                    #neo-optimize--default-settings-root .neo-optimize--choice-button[aria-pressed="true"] .neo-optimize--choice-saving { color: #a7f3c1; }
                    #neo-optimize--default-settings-root .neo-optimize--preview { position: relative; width: 800px; max-width: 100%; height: 200px; overflow: hidden; border-radius: 8px; background: #f0f0f1; }
                    #neo-optimize--default-settings-root .neo-optimize--preview img { display: block; width: 100%; height: 100%; object-fit: cover; }
                    #neo-optimize--default-settings-root .neo-optimize--preview-progress { display: none; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: rgba(255, 255, 255, 0.28); z-index: 2; }
                    #neo-optimize--default-settings-root .neo-optimize--preview-progress::before { content: ""; display: block; width: 0; height: 100%; background: #007DBA; animation: neo-optimize--preview-progress 100s linear forwards; }
                    #neo-optimize--default-settings-root[data-preview-loading="true"] .neo-optimize--preview-progress { display: block; }
                    #neo-optimize--default-settings-root[data-preview-loading="true"] .neo-optimize--preview-progress::before { animation-name: neo-optimize--preview-progress; }
                    #neo-optimize--default-settings-root .neo-optimize--preview-overlay { position: absolute; left: 10px; right: 10px; bottom: 10px; padding: 7px 9px; border-radius: 6px; background: rgba(0, 0, 0, 0.72); color: #fff; font-size: 1em; line-height: 1.35; }

                    #neo-optimize--default-settings-root neo-pro-crown-neo-optimize { width: 1em; height: 1em; display: inline-flex; }

                    @keyframes neo-optimize--preview-progress { 0% { width: 0%; } 2% { width: 12.944944%; } 4% { width: 24.214172%; } 6% { width: 34.024604%; } 8% { width: 42.565082%; } 10% { width: 50%; } 12% { width: 56.472472%; } 14% { width: 62.107086%; } 16% { width: 67.012302%; } 18% { width: 71.282541%; } 20% { width: 75%; } 22% { width: 78.236236%; } 24% { width: 81.053543%; } 26% { width: 83.506151%; } 28% { width: 85.641271%; } 30% { width: 87.5%; } 32% { width: 89.118118%; } 34% { width: 90.526771%; } 36% { width: 91.753076%; } 38% { width: 92.820635%; } 40% { width: 93.75%; } 42% { width: 94.559059%; } 44% { width: 95.263386%; } 46% { width: 95.876538%; } 48% { width: 96.410318%; } 50% { width: 96.875%; } 52% { width: 97.279529%; } 54% { width: 97.631693%; } 56% { width: 97.938269%; } 58% { width: 98.205159%; } 60% { width: 98.4375%; } 62% { width: 98.639765%; } 64% { width: 98.815846%; } 66% { width: 98.969134%; } 68% { width: 99.102579%; } 70% { width: 99.21875%; } 72% { width: 99.319882%; } 74% { width: 99.407923%; } 76% { width: 99.484567%; } 78% { width: 99.55129%; } 80% { width: 99.609375%; } 82% { width: 99.659941%; } 84% { width: 99.703962%; } 86% { width: 99.742284%; } 88% { width: 99.775645%; } 90% { width: 99.804688%; } 92% { width: 99.829971%; } 94% { width: 99.851981%; } 96% { width: 99.871142%; } 98% { width: 99.887822%; } 100% { width: 99.902344%; } }
                    @media (max-width: 700px) { #neo-optimize--default-settings-root .neo-optimize--choice-button { flex-basis: calc((100% - 8px) / 2); } }
                <?php \NeoOptimize\NeoGlobal\backend_page_style_tag_end(); ?>
                <div class="neo-optimize--choice-group" data-choice-group="output-format"><span class="neo-optimize--setting-label-text"><?php \NeoOptimize\NeoGlobal\echo_neo__("Output format", "Ausgabeformat"); ?><neo-info-tooltip-neo-optimize><?php \NeoOptimize\NeoGlobal\echo_neo__("AVIF usually creates smaller files than WebP at comparable visual quality. If AVIF is not available on the server, use WebP.", "AVIF erzeugt bei vergleichbarer visueller Qualität meist kleinere Dateien als WebP. Wenn AVIF auf dem Server nicht verfügbar ist, verwende WebP."); ?><br><?php \NeoOptimize\NeoGlobal\echo_neo__("Clear the cache to apply this to already optimized images.", "Leere den Cache, um das für bereits optimierte Bilder zu übernehmen."); ?></neo-info-tooltip-neo-optimize></span><div class="neo-optimize--choice-list">
                    <?php foreach (optimize_preview_setting_choices()["output_formats"] as $output_format_option) { ?><button type="button" class="neo-optimize--choice-button" data-output-format="<?php echo esc_attr($output_format_option); ?>" data-output-format-supported="<?php echo optimize_format_support($output_format_option) ? "true" : "false"; ?>" aria-pressed="<?php echo $selected_output_format === $output_format_option ? "true" : "false"; ?>"><?php if ($output_format_option === optimize_preview_setting_choices()["default_output_format"]) { ?><span class="neo-optimize--choice-default"><?php \NeoOptimize\NeoGlobal\echo_neo__("Recommended", "Empfohlen"); ?></span><?php } ?><span class="neo-optimize--choice-value"><?php echo esc_html(strtoupper($output_format_option)); ?></span><span class="neo-optimize--choice-size" data-size-for-output-format="<?php echo esc_attr($output_format_option); ?>">...</span></button><?php } ?> 
                </div>
                <?php if (!$webp_supported && $avif_supported) { ?><p class="neo-optimize--format-unsupported-message"><?php \NeoOptimize\NeoGlobal\echo_neo__("This server does not support WebP compression. neoOptimize automatically uses AVIF instead.", "Dieser Server unterstützt keine WebP-Komprimierung. neoOptimize verwendet automatisch AVIF."); ?></p><?php } ?> 
                <?php if (!$avif_supported && $webp_supported) { ?><p class="neo-optimize--format-unsupported-message"><?php \NeoOptimize\NeoGlobal\echo_neo__("This server does not support AVIF compression. neoOptimize automatically uses WebP instead.", "Dieser Server unterstützt keine AVIF-Komprimierung. neoOptimize verwendet automatisch WebP."); ?></p><?php } ?> 
                <?php if (!$webp_supported && !$avif_supported) { ?><p class="neo-optimize--format-unsupported-message"><?php \NeoOptimize\NeoGlobal\echo_neo__("This server supports neither WebP nor AVIF compression. neoOptimize delivers the original images unchanged. Install or enable WebP or AVIF support for PHP GD or ImageMagick yourself, or ask your web host to do so.", "Dieser Server unterstützt weder WebP- noch AVIF-Komprimierung. neoOptimize liefert die Originalbilder unverändert aus. Installiere oder aktiviere selbst die WebP- oder AVIF-Unterstützung für PHP GD oder ImageMagick oder bitte deinen Webhoster darum."); ?></p><?php } ?> 
                <p class="neo-optimize--avif-cache-hint" style="display: none;"><?php \NeoOptimize\NeoGlobal\echo_neo__("Should the neoOptimize cache be cleared to remove old image formats? Remember to also clear your website caching plugin’s cache after deleting it.", "Soll der neoOptimize-Cache geleert werden, um alte Bildformate aufzuräumen? Denke daran, nach dem Löschen auch den Cache deines Website-Caching-Plugins zu leeren."); ?> <button type="button" class="button button-small neo-optimize--clear-cache-button"><?php \NeoOptimize\NeoGlobal\echo_neo__("Clear cache", "Cache leeren"); ?></button></p>
                </div>
                <div class="neo-optimize--choice-group" data-choice-group="quality"><span class="neo-optimize--setting-label-text"><?php \NeoOptimize\NeoGlobal\echo_neo__("Image quality", "Bildqualität"); ?><neo-info-tooltip-neo-optimize><?php \NeoOptimize\NeoGlobal\echo_neo__("Controls image compression. Lower values create smaller files with more visible compression artifacts.", "Steuert die Bildkomprimierung. Niedrigere Werte erzeugen kleinere Dateien mit sichtbareren Kompressionsartefakten."); ?><br><?php \NeoOptimize\NeoGlobal\echo_neo__("Clear the cache to apply this to already optimized images.", "Leere den Cache, um das für bereits optimierte Bilder zu übernehmen."); ?></neo-info-tooltip-neo-optimize></span><div class="neo-optimize--choice-list">
                    <?php foreach (optimize_preview_setting_choices()["qualities"] as $quality_option) { ?><button type="button" class="neo-optimize--choice-button" data-quality="<?php echo esc_attr($quality_option); ?>" aria-pressed="<?php echo $selected_quality === $quality_option ? "true" : "false"; ?>"><?php if ($quality_option === optimize_preview_setting_choices()["default_quality"]) { ?><span class="neo-optimize--choice-default"><?php \NeoOptimize\NeoGlobal\echo_neo__("Recommended", "Empfohlen"); ?></span><?php } ?><span class="neo-optimize--choice-value"><?php echo esc_html($quality_option . "%"); ?></span><span class="neo-optimize--choice-size" data-size-for-quality="<?php echo esc_attr($quality_option); ?>">...</span></button><?php } ?> 
                </div></div>

                <div class="neo-optimize--choice-group" data-choice-group="retina-factor"><span class="neo-optimize--setting-label-text"><?php \NeoOptimize\NeoGlobal\echo_neo__("Retina factor", "Retina-Faktor"); ?><neo-info-tooltip-neo-optimize><?php \NeoOptimize\NeoGlobal\echo_neo__("Controls the generated pixel density. Higher values look sharper on high-resolution displays but increase data transfer.", "Steuert die generierte Pixeldichte. Höhere Werte wirken auf hochauflösenden Displays schärfer, erhöhen aber den Datentransfer."); ?><br><?php \NeoOptimize\NeoGlobal\echo_neo__("Clear the cache to apply this to already optimized images.", "Leere den Cache, um das für bereits optimierte Bilder zu übernehmen."); ?></neo-info-tooltip-neo-optimize></span><div class="neo-optimize--choice-list">
                    <?php foreach (optimize_preview_setting_choices()["retina_factors"] as $retina_factor_option) { $retina_factor_disabled_attr = ""; $retina_factor_size_text = "";  $retina_factor_disabled_attr = $selected_retina_factor !== $retina_factor_option ? " disabled" : ""; ?><button type="button" class="neo-optimize--choice-button" data-retina-factor="<?php echo esc_attr($retina_factor_option); ?>" aria-pressed="<?php echo $selected_retina_factor === $retina_factor_option ? "true" : "false"; ?>"<?php echo esc_attr($retina_factor_disabled_attr); ?>><?php if ($retina_factor_disabled_attr !== "") { ?><neo-pro-crown-neo-optimize class="neo-optimize--choice-crown"></neo-pro-crown-neo-optimize><?php } ?><?php if ($retina_factor_option === optimize_preview_setting_choices()["default_retina_factor"]) { ?><span class="neo-optimize--choice-default"><?php \NeoOptimize\NeoGlobal\echo_neo__("Recommended", "Empfohlen"); ?></span><?php } ?><span class="neo-optimize--choice-value"><?php echo esc_html(number_format($retina_factor_option / 100, 2) . "x"); ?></span><span class="neo-optimize--choice-size" data-size-for-retina-factor="<?php echo esc_attr($retina_factor_option); ?>"><?php echo esc_attr($retina_factor_size_text); ?></span></button><?php } ?> 
                </div></div>

                <span class="neo-optimize--setting-label-text"><?php \NeoOptimize\NeoGlobal\echo_neo__("Settings preview", "Einstellungsvorschau"); ?><neo-info-tooltip-neo-optimize><?php \NeoOptimize\NeoGlobal\echo_neo__("The preview image uses the currently selected output format. When supported, we explicitly recommend AVIF, 75% quality and a 1.5x retina factor to keep small text in images sharp.", "Das Vorschaubild nutzt das aktuell ausgewählte Ausgabeformat. Bei vorhandenem Support empfehlen wir ausdrücklich AVIF, 75% Qualität und Retina-Faktor 1,5x, um auch kleine Texte in Bildern scharf darzustellen."); ?></neo-info-tooltip-neo-optimize></span>

                <div class="neo-optimize--preview"><img data-preview-img alt=""><div class="neo-optimize--preview-progress" aria-hidden="true"></div><div class="neo-optimize--preview-overlay" data-preview-overlay><?php \NeoOptimize\NeoGlobal\echo_neo__("Loading preview images...", "Vorschaubilder laden..."); ?></div></div>
            </div>
        </neo-setting-neo-optimize>
        <neo-setting-neo-optimize>
            <div slot="left">
                <h3><?php \NeoOptimize\NeoGlobal\echo_neo__("Clear cache", "Cache leeren"); ?></h3>
                <p><?php \NeoOptimize\NeoGlobal\echo_neo__("Deletes all optimized images from the cache. Useful after tests or major image-size changes in the page builder. Images are regenerated automatically when requested again.", "Löscht alle optimierten Bilder aus dem Cache. Hilfreich nach Tests oder größeren Änderungen an Bildgrößen im Page Builder. Bilder werden bei der nächsten Anfrage automatisch neu generiert."); ?></p></div>
            <div slot="right">
                <neo-button-neo-optimize class="neo-optimize--clear-cache-button"><?php \NeoOptimize\NeoGlobal\echo_neo__("Clear cache", "Cache leeren"); ?></neo-button-neo-optimize>
            </div>
        </neo-setting-neo-optimize><?php
    };
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoSettings\interface_add_neo_setting_20260326')("neo-optimize", $settings_render_callback);
    $enqueue_assets_callback = function () { \NeoOptimize\NeoGlobal\enqueue_js_variable_backend("neoOptimizeIsPlayground", \NeoOptimize\NeoGlobal\is_playground()); \NeoOptimize\NeoGlobal\enqueue_js("neo-optimize--settings.js"); };
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($enqueue_assets_callback);
});

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-optimize", \NeoOptimize\NeoGlobal\add_or_update_query_params(admin_url("admin.php?page=" . \NeoOptimize\NeoGlobal\plugin_settings_page_slug()), ["neo-freemius--suppress-opt-in" => "true", "neo-settings--open-section" => "neo-optimize"]));
});
