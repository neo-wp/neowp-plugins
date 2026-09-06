<?php
namespace NeoAlt\NeoAlt; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function page_slug() { return "neo-alt"; }
\NeoAlt\NeoGlobal\add_action_hook("admin_menu", function () {
    add_management_page("neoAlt", "neoAlt", \NeoAlt\NeoGlobal\capability__neo_alt(), page_slug(), function () {
        $media_query = new \WP_Query(["post_type" => "attachment", "post_status" => "inherit", "posts_per_page" => -1, "orderby" => "date", "order" => "DESC"]);
        $media_items = [];
        foreach ($media_query->posts as $media_post) {
            $media_url = wp_get_attachment_url($media_post->ID);
            if (!$media_url) { \NeoAlt\NeoGlobal\global_warn_with_module_name("neo-alt", "Could not get URL for media with ID " . $media_post->ID . ". Skipping this media in neoAlt."); continue; }

            $attachment_metadata = \NeoAlt\NeoGlobal\post_meta($media_post->ID, "_wp_attachment_metadata");
            $thumbnail_url = null;
            if (isset($attachment_metadata["sizes"]) && ($attachment_metadata["width"] ?? 0) > 0 && ($attachment_metadata["height"] ?? 0) > 0) {
                $original_aspect_ratio = $attachment_metadata["width"] / $attachment_metadata["height"];
                $smallest_resolution = null;
                foreach ($attachment_metadata["sizes"] as $size_name => $size) {
                    if (!(($size["width"] ?? 0) > 0 && ($size["height"] ?? 0) > 0 && $size["height"] < 99999)) { continue; }
                    $size_aspect_ratio = $size["width"] / $size["height"];
                    if (!(abs($original_aspect_ratio - $size_aspect_ratio) <= 0.02)) { continue; }
                    $resolution = $size["width"] * $size["height"];
                    if ($smallest_resolution === null || $resolution < $smallest_resolution) { $thumbnail_source = wp_get_attachment_image_src($media_post->ID, $size_name); $thumbnail_url = $thumbnail_source[0] ?? null; $smallest_resolution = $resolution; }
                }
            }
            [$cached_thumbnail_urls, $interface_ok] = \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoOptimize\interface_list_cached_images_20250302')($media_url);
            if (!$interface_ok) { $cached_thumbnail_urls = []; }
            $thumbnail_url ??= array_keys($cached_thumbnail_urls)[0] ?? $media_url;
            $media_items[] = ["id" => $media_post->ID, "imgUrl" => \NeoAlt\NeoGlobal\percent_encode_invalid_utf8_url_bytes($media_url), "thumbnailUrl" => \NeoAlt\NeoGlobal\percent_encode_invalid_utf8_url_bytes($thumbnail_url), "filename" => \NeoAlt\NeoGlobal\percent_encode_invalid_utf8_url_bytes(rawurldecode(basename(\NeoAlt\NeoGlobal\remove_all_query_params($media_url)))), "title" => $media_post->post_title, "altText" => \NeoAlt\NeoGlobal\post_meta($media_post->ID, "_wp_attachment_image_alt") ?: "", "uploadDate" => $media_post->post_date_gmt, "modifiedDate" => $media_post->post_modified_gmt];
        }

        [$settings_section_url, $interface_ok] = \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-alt");
        if (!$interface_ok) { $settings_section_url = ""; }

        ?><div class="wrap neo-alt--wrap">
            <h1 id="neo-alt--heading">
                neoAlt

                <?php if (\NeoAlt\NeoGlobal\is_module_available("neo-feedback")) { ?> 
                    <neo-info-tooltip-neo-alt no-click-open instant-hover>
                        <button slot="icon" id="neo-alt--feedback-button" type="button" aria-label="<?php \NeoAlt\NeoGlobal\echo_neo_attr__("Give feedback", "Feedback geben") ?>">
                            <img src="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/message-square.svg" alt="">
                        </button>
                        <?php \NeoAlt\NeoGlobal\echo_neo__("Give feedback", "Feedback geben") ?> 
                    </neo-info-tooltip-neo-alt><?php
                } ?>

                <?php if ($settings_section_url) { ?> 
                    <neo-info-tooltip-neo-alt no-click-open instant-hover>
                        <a slot="icon" id="neo-alt--settings-button" href="<?php echo esc_url($settings_section_url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php \NeoAlt\NeoGlobal\echo_neo_attr__("Open settings", "Einstellungen öffnen") ?>">
                            <img src="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/settings.svg" alt="">
                        </a>
                        <?php \NeoAlt\NeoGlobal\echo_neo__("Open settings", "Einstellungen öffnen") ?> 
                    </neo-info-tooltip-neo-alt><?php
                } ?>
            </h1>
            <div id="neo-alt--toolbar">
                <div id="neo-alt--table-actions">
                    <neo-info-tooltip-neo-alt id="neo-alt--search-tooltip" no-click-open instant-hover><div slot="icon" id="neo-alt--search"><input type="search" id="neo-alt--search-input" placeholder="<?php \NeoAlt\NeoGlobal\echo_neo_attr__("Search", "Suche") ?>" title="<?php \NeoAlt\NeoGlobal\echo_neo_attr__("Search title and filename. Use | for OR, space for AND, * as wildcard and - before a term to exclude it", "Titel und Dateiname durchsuchen. Nutze | für ODER, Leerzeichen für UND, * als Wildcard und - vor einem Begriff zum Ausschließen") ?>"><button type="button" id="neo-alt--search-clear" aria-label="<?php \NeoAlt\NeoGlobal\echo_neo_attr__("Clear search", "Suche leeren") ?>">×</button></div><span class="neo-alt--disabled-control-message"><?php \NeoAlt\NeoGlobal\echo_neo__("Save or discard all changes before adjusting your search.", "Speichere oder verwirf alle Änderungen, um die Suche anzupassen.") ?></span></neo-info-tooltip-neo-alt>
                    <neo-info-tooltip-neo-alt id="neo-alt--empty-filter-tooltip" no-click-open instant-hover><neo-select-neo-alt slot="icon" id="neo-alt--empty-filter" value="all"><option value="all" selected data-icon-url="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/grid-3x3.svg"><?php \NeoAlt\NeoGlobal\echo_neo__("All images", "Alle Bilder") ?></option><option value="empty" data-icon-url="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/grid-2x2.svg"><?php \NeoAlt\NeoGlobal\echo_neo__("Images with empty alt texts", "Bilder mit leeren Alt-Texten") ?></option></neo-select-neo-alt><span class="neo-alt--disabled-control-message"><?php \NeoAlt\NeoGlobal\echo_neo__("Save or discard all changes before adjusting this filter.", "Speichere oder verwirf alle Änderungen, um diesen Filter anzupassen.") ?></span></neo-info-tooltip-neo-alt>
                    <neo-select-neo-alt id="neo-alt--sort-select" value="upload-date">
                        <option value="upload-date" selected       data-icon-url="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/calendar-arrow-up.svg"><?php      \NeoAlt\NeoGlobal\echo_neo__("Sort by upload date", "Nach Upload-Datum sortieren") ?></option>
                        <option value="modified-date"              data-icon-url="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/calendar-clock.svg"><?php         \NeoAlt\NeoGlobal\echo_neo__("Sort by modified date", "Nach Änderungsdatum sortieren") ?></option>
                        <option value="title"                      data-icon-url="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/type.svg"><?php                   \NeoAlt\NeoGlobal\echo_neo__("Sort by title", "Nach Titel sortieren") ?></option>
                        <option value="url"                        data-icon-url="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/link.svg"><?php                   \NeoAlt\NeoGlobal\echo_neo__("Sort by URL", "Nach URL sortieren") ?></option>
                        <option value="alt-text-length-ascending"  data-icon-url="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/arrow-up-narrow-wide.svg"><?php   \NeoAlt\NeoGlobal\echo_neo__("Alt text length (shortest first)", "Alt-Text-Länge (kürzeste zuerst)") ?></option>
                        <option value="alt-text-length-descending" data-icon-url="<?php echo esc_url(\NeoAlt\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/arrow-down-wide-narrow.svg"><?php \NeoAlt\NeoGlobal\echo_neo__("Alt text length (longest first)", "Alt-Text-Länge (längste zuerst)") ?></option>
                    </neo-select-neo-alt>
                </div>
                <div id="neo-alt--bulk-controls">
                    <div id="neo-alt--bulk-mode" role="group" aria-label="<?php \NeoAlt\NeoGlobal\echo_neo_attr__("Generation mode", "Generierungsmodus") ?>">
                        <button type="button" data-neo-alt--bulk-mode="title"><?php \NeoAlt\NeoGlobal\echo_neo__("Title only", "Nur Titel") ?></button>
                        <button type="button" data-neo-alt--bulk-mode="alt" class="neo-alt--bulk-mode-selected"><?php \NeoAlt\NeoGlobal\echo_neo__("Alt text only", "Nur Alt-Text") ?></button>
                        <button type="button" data-neo-alt--bulk-mode="both"><?php \NeoAlt\NeoGlobal\echo_neo__("Both", "Beides") ?></button>
                    </div>
                    <neo-button-neo-alt id="neo-alt--bulk-generate-button"><?php \NeoAlt\NeoGlobal\echo_neo__("Generate selected (0)", "Ausgewählte generieren (0)") ?></neo-button-neo-alt>
                    <span id="neo-alt--bulk-progress" hidden></span>
                    <button type="button" id="neo-alt--bulk-cancel-button" hidden><?php \NeoAlt\NeoGlobal\echo_neo__("Cancel", "Abbrechen") ?></button>
                    <neo-button-neo-alt id="neo-alt--discard-all-button"><?php \NeoAlt\NeoGlobal\echo_neo__("Discard all", "Alle verwerfen") ?></neo-button-neo-alt>
                    <neo-button-neo-alt id="neo-alt--accept-all-button" success><?php \NeoAlt\NeoGlobal\echo_neo__("Save all", "Alle speichern") ?></neo-button-neo-alt>
                </div>
            </div>
            <div id="neo-alt--table-container">
                <label id="neo-alt--selection-header"><input type="checkbox" id="neo-alt--bulk-selection-checkbox" aria-label="<?php \NeoAlt\NeoGlobal\echo_neo_attr__("Select all visible media", "Alle sichtbaren Medien auswählen") ?>" checked></label>
                <div id="neo-alt--table" aria-live="polite"></div>
            </div>
            <div id="neo-alt--empty-state" hidden><?php \NeoAlt\NeoGlobal\echo_neo__("No media found.", "Keine Medien gefunden.") ?></div>
            <footer id="neo-alt--footer"><span id="neo-alt--media-count"></span><a href="<?php echo esc_url("https://" . \NeoAlt\NeoGlobal\option__neo_wp_com() . "/?ref=neo-alt") ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(\NeoAlt\NeoGlobal\option__neo_wp_com()) ?></a></footer>
            <neo-media-preview-popup-neo-alt id="neo-alt--preview-popup"></neo-media-preview-popup-neo-alt>
            <?php \NeoAlt\NeoGlobal\backend_page_script_tag_start(["id" => "neo-alt--media-data", "type" => "application/json"]); ?><?php echo \NeoAlt\NeoGlobal\php_to_js_object($media_items) /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?><?php \NeoAlt\NeoGlobal\backend_page_script_tag_end(); ?><!-- Alle Medien ohne zweiten Request an JS übergeben ( php_to_js_object gibt sicheres JSON aus) -->
        </div><?php
    });
});

\NeoAlt\NeoGlobal\add_action_hook("current_screen", function () {
    if (!\NeoAlt\NeoGlobal\current_user_can__neo_alt()) { return; }
    $current_screen = get_current_screen();
    \NeoAlt\NeoGlobal\enqueue_css("neo-alt--wp-integration.css");
    \NeoAlt\NeoGlobal\enqueue_js("neo-alt--wp-media-modal.js");
    if ($current_screen?->id === "attachment") { \NeoAlt\NeoGlobal\enqueue_js("neo-alt--wp-attachment-edit.js"); }
    if ($current_screen?->is_block_editor()) { \NeoAlt\NeoGlobal\enqueue_js("neo-alt--wp-gutenberg.js"); }
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoAltOverviewUrl", admin_url("tools.php?page=" . page_slug()));
});

\NeoAlt\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-alt", admin_url("tools.php?page=" . page_slug()));
});

\NeoAlt\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(get_current_screen()?->id === "tools_page_" . page_slug())) { return; }
    if (!\NeoAlt\NeoGlobal\current_user_can__neo_alt()) { return; }

    [$rename_settings_page_url, $interface_ok] = \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-rename");
    if (!$interface_ok) { $rename_settings_page_url = admin_url("admin.php?page=" . \NeoAlt\NeoGlobal\plugin_settings_page_slug()); }
    \NeoAlt\NeoGlobal\enqueue_css("neo-alt.css");
    \NeoAlt\NeoGlobal\enqueue_js("neo-alt.js");
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend("neoAltRenameSettingsPageUrl", $rename_settings_page_url);
});

\NeoAlt\NeoGlobal\register_rest_endpoint("/wp-json/neo/alt-save", "POST", fn () => \NeoAlt\NeoGlobal\current_user_can__neo_alt(), function ($get_param) {
    $request_started_at = microtime(true); $requested_item_count = 0; $saved_items = [];
    try {
        $items = $get_param("items"); $requested_item_count = count($items);
        foreach ($items as $item) {
            if (!is_array($item)) { \NeoAlt\NeoGlobal\throw_global_exception("Invalid item for neoAlt save request", status_code: 400); }
            $media_id = (int) ($item["media-id"] ?? 0); $media_post = get_post($media_id);
            if (!$media_post || $media_post->post_type !== "attachment") { \NeoAlt\NeoGlobal\throw_global_exception(\NeoAlt\NeoGlobal\neo__("Media not found.", "Medium nicht gefunden."), status_code: 404); }
            $save_fields = $item["fields"] ?? null;
            if (!(is_array($save_fields) && $save_fields !== [] && \NeoAlt\NeoGlobal\array_diff_better($save_fields, ["title", "alt"]) === [])) { \NeoAlt\NeoGlobal\throw_global_exception("Invalid fields for neoAlt save request", status_code: 400); }
            $old_title = get_post_field("post_title", $media_id, context: "raw"); $old_alt_text = \NeoAlt\NeoGlobal\post_meta($media_id, "_wp_attachment_image_alt") ?: "";
            $new_title    = in_array("title", $save_fields, strict: true) ? trim(sanitize_text_field($item["title"] ?? ""))        : $old_title;
            $new_alt_text = in_array("alt",   $save_fields, strict: true) ? trim(sanitize_textarea_field($item["alt-text"] ?? "")) : $old_alt_text;
            if ($new_title === $old_title && $new_alt_text === $old_alt_text) { $saved_items[] = ["mediaId" => $media_id, "title" => $old_title, "altText" => $old_alt_text]; continue; }
            $old_path_rel = str_replace(\NeoAlt\NeoGlobal\uploads_dir() . "/", "", get_attached_file($media_id)); $old_slug = get_post_field("post_name", $media_id, context: "raw");
            if ($new_title !== $old_title) { $updated_media_id = wp_update_post(["ID" => $media_id, "post_title" => $new_title], true); if (is_wp_error($updated_media_id)) { \NeoAlt\NeoGlobal\throw_global_exception($updated_media_id->get_error_message(), status_code: 500); } }
            if ($new_alt_text !== $old_alt_text) { if ($new_alt_text === "") { delete_post_meta($media_id, "_wp_attachment_image_alt"); } else { update_post_meta($media_id, "_wp_attachment_image_alt", wp_slash($new_alt_text)); } }
            $merge_event = $new_title !== $old_title && $new_alt_text !== $old_alt_text ? "both" : ($new_title !== $old_title ? "title" : "alt-text");
            [$_, $interface_ok] = \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoRenameUndo\interface_save_rename_for_undo_20250915')($media_id, $old_path_rel, $old_title, $old_slug, $old_alt_text, merge_event: $merge_event);
            if (!$interface_ok) { \NeoAlt\NeoGlobal\global_log_with_module_name("neo-alt", "neoRename undo interface is unavailable for neoAlt. (deduplicated)"); }
            $saved_items[] = ["mediaId" => $media_id, "title" => get_post_field("post_title", $media_id, context: "raw"), "altText" => \NeoAlt\NeoGlobal\post_meta($media_id, "_wp_attachment_image_alt") ?: ""];
        }
        \NeoAlt\NeoGlobal\global_log_with_module_name("neo-alt", "neoAlt save completed for " . count($saved_items) . " media items in " . round((microtime(true) - $request_started_at) * 1000) . " ms.");

        return ["items" => $saved_items];
    } catch (\Throwable $error) {
        \NeoAlt\NeoGlobal\global_log_with_module_name("neo-alt", "neoAlt save failed after " . count($saved_items) . " of " . $requested_item_count . " media items in " . round((microtime(true) - $request_started_at) * 1000) . " ms with " . get_class($error) . ".");
        throw $error;
    }
});
