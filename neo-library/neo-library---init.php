<?php
namespace NeoLibrary\NeoLibrary; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function get_neo_library_menu_page_slug() { return "neo-library"; }
function interface_neo_library_menu_page_slug_20250618() { return get_neo_library_menu_page_slug(); }
function interface_neo_library_menu_page_url_20250618() { return admin_url("upload.php?page=" . get_neo_library_menu_page_slug()); }

\NeoLibrary\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {
        ?><neo-setting-neo-library><?php \NeoLibrary\NeoGlobal\echo_neo__("No settings", "Keine Einstellungen") ?></neo-setting-neo-library><?php
    };
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoSettings\interface_add_neo_setting_20260326')("neo-library", $settings_render_callback);
});

\NeoLibrary\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-library", admin_url("upload.php?page=" . get_neo_library_menu_page_slug()) . "#neo-library--open-tutorial");
});

\NeoLibrary\NeoGlobal\add_action_hook("load-upload.php:0", function () {
    if (\NeoLibrary\NeoGlobal\query_param("page") === get_neo_library_menu_page_slug()) { return; }
    if (\NeoLibrary\NeoGlobal\query_param("neo-library--suppress-redirect") !== null) { return; }
    if (!\NeoLibrary\NeoGlobal\current_user_can__neo_library__view()) { return; }

    $use_neolibrary = !\NeoLibrary\NeoGlobal\cookie_exists("neo-library--use-neo-library") || \NeoLibrary\NeoGlobal\cookie_get_sanitized("neo-library--use-neo-library") === "true";
    $force_old_media_lib = \NeoLibrary\NeoGlobal\query_param("force-old-media-lib") === "true";
    if ($use_neolibrary && !$force_old_media_lib) {
        \NeoLibrary\NeoGlobal\cookie_set("neo-library--use-neo-library", "true", time() + (365 * 24 * 60 * 60), "/");
        wp_safe_redirect(admin_url("upload.php?page=neo-library"));
        exit;
    } else {
        \NeoLibrary\NeoGlobal\cookie_set("neo-library--use-neo-library", "false", time() + (365 * 24 * 60 * 60), "/");
    }
});

\NeoLibrary\NeoGlobal\add_action_hook("admin_menu", function () {
    add_submenu_page(
        "upload.php",
        \NeoLibrary\NeoGlobal\neo__("neoLibrary", "neoLibrary"),
        \NeoLibrary\NeoGlobal\neo__("neoLibrary", "neoLibrary"),
        \NeoLibrary\NeoGlobal\capability__neo_library(),
        get_neo_library_menu_page_slug(),
        function () {
            $media_query = new \WP_Query([
                "post_type"      => "attachment",
                "post_status"    => "inherit",
                "posts_per_page" => -1,
                "orderby"        => "date",
                "order"          => "DESC"
            ]);
            $images = [];
            foreach ($media_query->posts as $img_post) {
                $image_url = wp_get_attachment_url($img_post->ID);
                if (!$image_url) { \NeoLibrary\NeoGlobal\global_warn_with_module_name("neo-library", "Could not get URL for image with ID " . $img_post->ID . ". Skipping this image in the neoLibrary."); continue; }
                $slug          = $img_post->post_name;
                $upload_date   = $img_post->post_date_gmt;
                $modified_date = $img_post->post_modified_gmt;

                $attachment_metadata = \NeoLibrary\NeoGlobal\post_meta($img_post->ID, "_wp_attachment_metadata");

                $thumbnail_url = null;
                if (isset($attachment_metadata["sizes"]) && ($attachment_metadata["width"] ?? 0) > 0 && ($attachment_metadata["height"] ?? 0) > 0) {
                    $original_aspect_ratio = $attachment_metadata["width"] / $attachment_metadata["height"];
                    $smallest_resolution = null;
                    foreach ($attachment_metadata["sizes"] as $size_name => $size) {
                        if (!(($size["width"] ?? 0) > 0 && ($size["height"] ?? 0) > 0 && $size["height"] < 99999)) { \NeoLibrary\NeoGlobal\global_warn_with_module_name("neo-library", "Detected invalid width or height for image " . $image_url . " and size " . $size_name . ". Skipping this thumbnail in the neoLibrary. (deduplicated)"); continue; }
                        $size_aspect_ratio = $size["width"] / $size["height"];
                        if (!(abs($original_aspect_ratio - $size_aspect_ratio) <= 0.02)) { continue; }
                        $resolution = $size["width"] * $size["height"];
                        if ($smallest_resolution === null || $resolution < $smallest_resolution) {
                            $thumbnail_url = wp_get_attachment_image_src($img_post->ID, $size_name)[0];
                            $smallest_resolution = $resolution;
                        }
                    }
                }

                [$cached_thumbnail_urls, $interface_ok] = \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoOptimize\interface_list_cached_images_20250302')($image_url);
                if (!$interface_ok) { $cached_thumbnail_urls = []; }
                $thumbnail_url ??= array_keys($cached_thumbnail_urls)[0] ?? $image_url;

                static $author_name_cache = []; $author_name_cache[$img_post->post_author] ??= get_the_author_meta("display_name", $img_post->post_author);
                $images[] = [ "imgUrl" => \NeoLibrary\NeoGlobal\percent_encode_invalid_utf8_url_bytes($image_url), "slug" => $slug, "thumbnailUrl" => \NeoLibrary\NeoGlobal\percent_encode_invalid_utf8_url_bytes($thumbnail_url), "title" => $img_post->post_title, "uploadDate" => $upload_date, "modifiedDate" => $modified_date, "author" => $author_name_cache[$img_post->post_author], "postId" => $img_post->ID];
            }

            [$neo_library_settings_section_url, $interface_ok] = \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-library");
            ?>

            <div class="wrap">
                <h1 id="neo-library--heading"><?php \NeoLibrary\NeoGlobal\echo_neo__("neoLibrary", "neoLibrary"); ?></h1>

                <div id="neo-library--table-settings">
                    <div id="neo-library--mode-switch">
                        <button id="neo-library--mode-switch-list">       <img src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url() . "/img/neo-library--switch-list-icon.svg") ?>"    alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("List Mode", "Listenansicht") ?>"></button>
                        <button id="neo-library--mode-switch-grid">       <img src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url() . "/img/neo-library--switch-grid-icon.svg") ?>"    alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Grid Mode", "Gitteransicht") ?>"></button>
                        <button id="neo-library--mode-switch-neo-library"><img src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url() . "/img/neo-library--switch-neolist-icon.svg") ?>" alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("neoLibrary", "neoLibrary") ?>"></button>
                        <div id="neo-library--old-media-library-hint" style="display:none;">
                            <?php \NeoLibrary\NeoGlobal\echo_neo__("← Back to the classic media view.", "← Zurück zur klassischen Medien-Ansicht.") ?>
                            <button id="neo-library--old-media-library-hint-button"><?php \NeoLibrary\NeoGlobal\echo_neo__("OK", "OK") ?></button>
                        </div>
                    </div>

                    <div id="neo-library--settings-controls-right">
                        <button id="neo-library--bulk-rename-button" data-neo-library--tooltip="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Bulk rename", "Bulk umbenennen") ?>">
                            <img class="neo-library--icon" src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/img/neo-library--button-rename-icon.svg" alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Bulk rename", "Bulk umbenennen") ?>">
                        </button>

                        <button id="neo-library--clear-cache-button" data-neo-library--tooltip="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Clear cache", "Cache leeren") ?>">
                            <img class="neo-library--icon" src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/img/neo-library--header-clear-icon.svg" alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Clear cache", "Cache leeren") ?>">
                        </button>

                        <div id="neo-library--sort-select-button" data-neo-library--tooltip="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Sort", "Sortieren") ?>">
                            <neo-select-neo-library id="neo-library--sort-select" selected-display="empty">
                                <option value="upload-date"   data-icon-url="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/calendar-arrow-up.svg"><?php \NeoLibrary\NeoGlobal\echo_neo__("Sort by upload date (default)", "Nach Upload-Datum sortieren (Standard)") ?></option>
                                <option value="modified-date" data-icon-url="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/calendar-clock.svg"><?php    \NeoLibrary\NeoGlobal\echo_neo__("Sort by modified date",         "Nach Änderungsdatum sortieren")          ?></option>
                                <option value="title"         data-icon-url="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/type.svg"><?php              \NeoLibrary\NeoGlobal\echo_neo__("Sort by title",                 "Nach Titel sortieren")                   ?></option>
                                <option value="url"           data-icon-url="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/link.svg"><?php              \NeoLibrary\NeoGlobal\echo_neo__("Sort by URL",                   "Nach URL sortieren")                     ?></option>
                            </neo-select-neo-library>
                            <img src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url() . "/img/neo-library--header-sort-icon.svg") ?>" alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Sort", "Sortieren") ?>">
                        </div>

                        <?php if (\NeoLibrary\NeoGlobal\is_module_available("neo-feedback")) { ?> 
                        <button id="neo-library--feedback-button" type="button" data-neo-library--tooltip="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Give feedback", "Feedback geben") ?>" title="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Give feedback", "Feedback geben") ?>" aria-label="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Give feedback", "Feedback geben") ?>">
                            <img class="neo-library--icon" src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/message-square.svg" alt="">
                        </button>
                        <?php } ?>
                        <?php if ($interface_ok) { ?> 
                        <a id="neo-library--settings-button" href="<?php echo esc_url($neo_library_settings_section_url) ?>" target="_blank" rel="noopener noreferrer" data-neo-library--tooltip="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Settings", "Einstellungen") ?>" title="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Settings", "Einstellungen") ?>" aria-label="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Settings", "Einstellungen") ?>">
                            <img class="neo-library--icon" src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/settings.svg" alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Settings", "Einstellungen") ?>">
                        </a>
                        <?php } ?>

                        <div id="neo-library--search">
                            <input id="neo-library--search-input" placeholder="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Search", "Suche") ?>" title="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Search for media. Use | for OR, Space for AND, * as placeholder", "Suche für Medien. Möglich sind | für ODER, Leerzeichen für UND, * für Platzhalter") ?>" list="neo-library--search-input-suggestions" data-neo-library--search-state="empty" type="search">
                            <input id="neo-library--search-input-clear" type="button" value="×" title="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Clear search", "Suche löschen") ?>" onclick="document.getElementById('neo-library--search-input').value = ''; document.getElementById('neo-library--search-input').dispatchEvent(new Event('input'));">
                            <datalist id="neo-library--search-input-suggestions"></datalist>
                        </div>
                    </div>
                </div>

                <input type="file" id="neo-library--file-selector" multiple style="display: none;">

                <div id="neo-library--table">
                    <div id="neo-library--create-row" class="neo-library--row">
                        <div class="neo-library--column-image neo-library--hide-skeleton">
                            <img src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/img/_global-placeholder-image-icon.svg" alt="<?php \NeoLibrary\NeoGlobal\echo_neo__("Upload", "Hochladen") ?>">
                        </div>
                        <div class="neo-library--column-info neo-library--hide-skeleton">
                            <strong class="neo-library--info-title"><?php \NeoLibrary\NeoGlobal\echo_neo__("Create neoDraw", "neoDraw erstellen") ?></strong>
                            <div class="neo-library--info-rel-path">
                                <span><?php \NeoLibrary\NeoGlobal\echo_neo__("Click to create a new blank neoDraw SVG file.", "Hier klicken, um eine neue neoDraw SVG Datei zu erstellen.") ?></span>
                            </div>
                        </div>
                        <div class="neo-library--column-buttons">
                            <button><img class="neo-library--icon" src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url() . "/img/neo-library--add-create-icon.svg") ?>" alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Create", "Erstellen") ?>"></button>
                        </div>
                    </div>

                    <div id="neo-library--upload-row" class="neo-library--row">
                        <div class="neo-library--column-image neo-library--hide-skeleton">
                            <img src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url() . "/img/_global-placeholder-image-icon.svg") ?>" alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Upload", "Hochladen") ?>">
                        </div>
                        <div class="neo-library--column-info neo-library--hide-skeleton">
                            <strong class="neo-library--info-title"><?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Upload a file", "Datei hochladen") ?></strong>
                            <div class="neo-library--info-rel-path">
                                <span><?php \NeoLibrary\NeoGlobal\echo_neo__("Drag and drop your images here to upload them, or click to choose.", "Ziehe Bilder hierher, um sie hochzuladen, oder klicke zum Auswählen.") ?></span>
                            </div>
                        </div>
                        <div class="neo-library--column-buttons">
                            <button><img class="neo-library--icon" src="<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url() . "/img/neo-library--add-upload-icon.svg") ?>" alt="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__("Upload", "Hochladen") ?>"></button>
                        </div>
                    </div>

                    <?php \NeoLibrary\NeoGlobal\backend_page_script_tag_start(["data-neo-library--images" => \wp_specialchars_decode(esc_attr(\NeoLibrary\NeoGlobal\php_to_js_object($images)), ENT_QUOTES)]); ?>
                        for (const img of JSON.parse(document.currentScript.getAttribute("data-neo-library--images"))) {
                            const row = document.createElement("div");
                            row.className = "neo-library--row";
                            row.setAttribute("data-state", "ignored");
                            row.setAttribute("data-img-url", img.imgUrl);
                            row.setAttribute("data-thumbnail-img-url", img.thumbnailUrl);
                            row.setAttribute("data-upload-date", img.uploadDate);
                            row.setAttribute("data-modified-date", img.modifiedDate);
                            row.setAttribute("data-title", img.title);
                            row.setAttribute("data-author", img.author);
                            row.setAttribute("data-post-id", img.postId);
                            document.getElementById("neo-library--table").appendChild(row);
                        }
                    <?php \NeoLibrary\NeoGlobal\backend_page_script_tag_end(); ?>
                </div>
            </div>

            <footer id="neo-library--footer">
                <div></div>
                <div class="neo-library--image-count"></div>
                <div><a href="https://neo-wp.com/?ref=neo-library" target="_blank">neo-wp.com</a></div>
            </footer>

            <neo-media-preview-popup-neo-library id="neo-library--preview-popup"></neo-media-preview-popup-neo-library>
            <?php
        }
    );
});

\NeoLibrary\NeoGlobal\add_action_hook("current_screen", function () {
    global $pagenow;
    if ($pagenow !== "upload.php") { return; }
    if (!\NeoLibrary\NeoGlobal\current_user_can__neo_library__view()) { return; }
    if (\NeoLibrary\NeoGlobal\query_param("page") !== get_neo_library_menu_page_slug()) {
        \NeoLibrary\NeoGlobal\add_action_hook("admin_head", function () {
            ?><?php \NeoLibrary\NeoGlobal\backend_page_style_tag_start([]); ?>
                .view-switch>.view-neo-library { background-image: url('<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/img/neo-library--switch-neolist-icon.svg'); background-size: 18px; background-position: center; background-repeat: no-repeat; }
            <?php \NeoLibrary\NeoGlobal\backend_page_style_tag_end(); ?><?php
        });
        \NeoLibrary\NeoGlobal\enqueue_js("neo-library--classic-wp-media-library.js");
        return;
    }

    if (\NeoLibrary\NeoGlobal\is_module_available("neo-draw")) {
        \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoDraw\interface_draw_editor_dialog_init_20250302')();
    }

    \NeoLibrary\NeoGlobal\enqueue_css("neo-library.css");
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoLibraryUploadEndpointUrl",   \NeoLibrary\NeoGlobal\get_rest_endpoint_url("/wp-json/neo/library-upload"));
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoLibraryUploadEndpointNonce", wp_create_nonce("wp_rest"));
    if (version_compare(get_bloginfo("version"), "7.1-alpha", ">=") && \function_exists("wp_is_client_side_media_processing_enabled") && wp_is_client_side_media_processing_enabled() && wp_script_is("wp-upload-media", "registered") && wp_script_is("wp-media-utils", "registered")) { wp_enqueue_script("wp-upload-media"); wp_enqueue_script("wp-media-utils"); }
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoLibraryAllowedMimeTypes",    get_allowed_mime_types());
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoLibraryMaxUploadFileSize",   wp_max_upload_size());
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoLibraryPostDetailsUrl",      admin_url("post.php"));
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoLibraryMediaLibraryUrl",     admin_url("upload.php"));
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoLibrarySettingsPageUrl",     admin_url("admin.php?page=" . \NeoLibrary\NeoGlobal\plugin_settings_page_slug()));
    [$last_image_cachebust_change_date, $interface_ok] = \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoImageCachebust\interface_get_last_image_cachebust_change_date_20260604')(); \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoLibraryImageCachebustLastChangeDate", $interface_ok ? $last_image_cachebust_change_date : 0);
    \NeoLibrary\NeoGlobal\enqueue_js("neo-library.js");
});

function get_library_image_file_sizes($img_url) {
    [$cached_images, $interface_ok] = \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoOptimize\interface_list_cached_images_20250302')($img_url);
    if (!$interface_ok) { $cached_images = []; }

    $post_id = attachment_url_to_postid($img_url); if (!$post_id) { return ["raw" => null, "min" => null, "max" => null]; }
    $file_path = get_attached_file($post_id);
    if (!$file_path || !\NeoLibrary\NeoGlobal\fs_file_exists($file_path)) { return ["raw" => null, "min" => null, "max" => null]; }
    $file_url_min = array_keys($cached_images)[0] ?? null;
    $file_url_max = array_keys($cached_images)[count($cached_images) - 1] ?? null;
    $file_path_min = $cached_images[$file_url_min] ?? null;
    $file_path_max = $cached_images[$file_url_max] ?? null;

    $file_size_raw = \NeoLibrary\NeoGlobal\fs_filesize($file_path);
    $file_size_min = $file_path_min === null || !\NeoLibrary\NeoGlobal\fs_file_exists($file_path_min) ? null : \NeoLibrary\NeoGlobal\fs_filesize($file_path_min);
    $file_size_max = $file_path_max === null || !\NeoLibrary\NeoGlobal\fs_file_exists($file_path_max) ? null : \NeoLibrary\NeoGlobal\fs_filesize($file_path_max);

    if (function_exists("gzencode")) {
        $file_size_min_gzip = $file_size_min === null ? null : strlen(gzencode(\NeoLibrary\NeoGlobal\fs_file_get_contents($file_path_min), 9));
        if ($file_path_min === $file_path_max) { $file_size_max_gzip = $file_size_min_gzip; }
        else { $file_size_max_gzip = $file_size_max === null ? null : strlen(gzencode(\NeoLibrary\NeoGlobal\fs_file_get_contents($file_path_max), 9)); }
    } else {
        $file_size_min_gzip = $file_size_max_gzip = null;
    }

    return [
        "raw" =>                                  ["url" => \NeoLibrary\NeoGlobal\percent_encode_invalid_utf8_url_bytes($img_url),      "size" => $file_size_raw],
        "min" => $file_size_min === null ? null : ["url" => \NeoLibrary\NeoGlobal\percent_encode_invalid_utf8_url_bytes($file_url_min), "size" => $file_size_min, "sizeGzip" => $file_size_min_gzip],
        "max" => $file_size_max === null ? null : ["url" => \NeoLibrary\NeoGlobal\percent_encode_invalid_utf8_url_bytes($file_url_max), "size" => $file_size_max, "sizeGzip" => $file_size_max_gzip]
    ];
}

\NeoLibrary\NeoGlobal\register_rest_endpoint("/wp-json/neo/library-get-info", "POST", fn () => \NeoLibrary\NeoGlobal\current_user_can__neo_library__view(), function ($get_param) {
    $img_url_list = $get_param("img-urls");
    if (!is_array($img_url_list)) { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Invalid image URL list", "Ungültige Bild-URL-Liste"), status_code: 400, error_code: "neolibrary_get_info_invalid_request"); }
    $img_url_list = array_map(fn ($img_url) => \NeoLibrary\NeoGlobal\percent_decode_invalid_utf8_url_bytes($img_url), $img_url_list);
    $img_info_list = [];
    $all_db_usages_of_img_urls = \NeoLibrary\NeoGlobal\db_entries_image_usage_lookup($img_url_list, max_sql_time_seconds: 10, include_content_preview: \NeoLibrary\NeoGlobal\current_user_can__global_db_entries_details());
    if ($all_db_usages_of_img_urls === false) { $img_info_list = false; }
    foreach ($img_url_list as $img_url) {
        $post_id = attachment_url_to_postid($img_url); $img_post = get_post($post_id);
        if ($img_post === null) { continue; }
        $img_info_list[] = [
            "imgUrl" => \NeoLibrary\NeoGlobal\percent_encode_invalid_utf8_url_bytes($img_url),
            "postId" => $img_post->ID,
            "title" => $img_post->post_title,
            "dbEntriesUsingImage" => $all_db_usages_of_img_urls === false ? false : ($all_db_usages_of_img_urls[$img_url] ?? []),
            "fileSizes" => get_library_image_file_sizes($img_url)
        ];
    }
    return $img_info_list;
});

\NeoLibrary\NeoGlobal\register_rest_endpoint("/wp-json/neo/library-upload", "POST", fn () => \NeoLibrary\NeoGlobal\current_user_can__neo_library__edit(), function ($get_param) {
    \NeoLibrary\NeoGlobal\add_filter_hook("big_image_size_threshold",   function () { return false; });
    \NeoLibrary\NeoGlobal\add_filter_hook("wp_image_maybe_exif_rotate", function () { return false; });
    require_once(ABSPATH . "wp-admin/includes/file.php"); require_once(ABSPATH . "wp-admin/includes/media.php"); /* This authenticated REST endpoint requires WordPress's media upload API, which is not loaded for REST requests. #suppressLinterWporgDirectoryConstantCheck #suppressLinterWPorgAutoCoreIncludeCheck */
    require_once(ABSPATH . "wp-admin/includes/image.php"); /* This authenticated REST upload requires WordPress's image metadata API, which is not loaded for REST requests. #suppressLinterWporgDirectoryConstantCheck #suppressLinterWPorgAutoCoreIncludeCheck */
    \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoDraw\interface_enable_svg_uploads_20250302')();
    $attachment_id = media_handle_upload("file", 0);
    if (is_wp_error($attachment_id)) { \NeoLibrary\NeoGlobal\throw_global_exception($attachment_id->get_error_message(), status_code: 500, error_code: "neolibrary_upload_error"); }
    $clean_uploaded_title = sanitize_text_field($get_param("clean-uploaded-title")); if ($clean_uploaded_title !== "") { $post_id = wp_update_post(["ID" => $attachment_id, "post_title" => $clean_uploaded_title], true); if (is_wp_error($post_id)) { \NeoLibrary\NeoGlobal\throw_global_exception($post_id->get_error_message(), status_code: 500, error_code: "neolibrary_upload_title_error"); } }
    $img_url = wp_get_attachment_url($attachment_id); if (!$img_url) { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Could not find uploaded file URL", "Konnte URL der hochgeladenen Datei nicht finden"), status_code: 500, error_code: "neolibrary_upload_url_error"); }
    return ["imgUrl" => \NeoLibrary\NeoGlobal\percent_encode_invalid_utf8_url_bytes($img_url)];
});

\NeoLibrary\NeoGlobal\register_rest_endpoint("/wp-json/neo/library-update-title", "POST", fn () => \NeoLibrary\NeoGlobal\current_user_can__neo_library__edit(), function ($get_param) {
    $img_url = \NeoLibrary\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("img-url")); $post_id = attachment_url_to_postid(\NeoLibrary\NeoGlobal\remove_all_query_params($img_url));
    if (!$post_id) { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Image not found", "Bild nicht gefunden"), status_code: 404, error_code: "neolibrary_update_title_not_found"); }
    $clean_uploaded_title = sanitize_text_field($get_param("title"));
    if ($clean_uploaded_title === "") { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Invalid title", "Ungültiger Titel"), status_code: 400, error_code: "neolibrary_update_title_invalid_request"); }
    $updated_post_id = wp_update_post(["ID" => $post_id, "post_title" => $clean_uploaded_title], true);
    if (is_wp_error($updated_post_id)) { \NeoLibrary\NeoGlobal\throw_global_exception($updated_post_id->get_error_message(), status_code: 500, error_code: "neolibrary_update_title_error"); }
    return "OKAY";
});

\NeoLibrary\NeoGlobal\register_rest_endpoint("/wp-json/neo/library-correct-upload-order", "POST", fn () => \NeoLibrary\NeoGlobal\current_user_can__neo_library__edit(), function ($get_param) {
    $img_urls = $get_param("img-urls");
    if (!is_array($img_urls)) { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Invalid image URL list", "Ungültige Bild-URL-Liste"), status_code: 400, error_code: "neolibrary_correct_upload_order_invalid_request"); }
    $img_urls = array_map(fn ($img_url) => \NeoLibrary\NeoGlobal\percent_decode_invalid_utf8_url_bytes($img_url), $img_urls);
    $img_ids  = array_map("attachment_url_to_postid", $img_urls);

    $img_ids = \NeoLibrary\NeoGlobal\array_filter_better($img_ids, function ($id) { return $id !== 0; });
    if (empty($img_ids)) { return "OKAY"; }

    $min_date_utc   = min(array_map(function ($id) { return get_post($id)->post_date_gmt; }, $img_ids));
    $min_date_local = min(array_map(function ($id) { return get_post($id)->post_date; },     $img_ids));

    foreach ($img_ids as $index => $id) {
        $img_post = get_post($id);
        $img_post->post_date_gmt = \NeoLibrary\NeoGlobal\utc_date_string("Y-m-d H:i:s", \NeoLibrary\NeoGlobal\timestamp_from_utc_date_string($min_date_utc)  + $index);
        $img_post->post_date     = \NeoLibrary\NeoGlobal\wp_date_string("Y-m-d H:i:s",  \NeoLibrary\NeoGlobal\timestamp_from_wp_date_string($min_date_local) + $index);
        $img_post->post_modified_gmt = $img_post->post_date_gmt;
        $img_post->post_modified     = $img_post->post_date;
        $post_id = wp_update_post($img_post, true);
        if (is_wp_error($post_id)) { \NeoLibrary\NeoGlobal\throw_global_exception($post_id->get_error_message(), status_code: 500, error_code: "neolibrary_correct_upload_order_error"); }
    }
    return "OKAY";
});

\NeoLibrary\NeoGlobal\register_rest_endpoint("/wp-json/neo/library-delete", "POST", fn () => \NeoLibrary\NeoGlobal\current_user_can__neo_library__edit(), function ($get_param) {
    $img_url = \NeoLibrary\NeoGlobal\percent_decode_invalid_utf8_url_bytes($get_param("img-url"));
    $post_id = attachment_url_to_postid($img_url);
    if (!$post_id) { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Image not found", "Bild nicht gefunden"), status_code: 404, error_code: "neolibrary_delete_not_found"); }
    $success = wp_delete_attachment($post_id, true);
    if (!$success) { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Could not delete database entry", "Konnte Datenbankeintrag nicht löschen"), status_code: 500, error_code: "neolibrary_delete_error"); }
    return "OKAY";
});

\NeoLibrary\NeoGlobal\register_rest_endpoint("/wp-json/neo/library-clear-cache", "POST", fn () => \NeoLibrary\NeoGlobal\current_user_can__neo_library__edit(), function () {
    try { \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoGlobal\interface_all_bundles_clear_cache_20260202', throw_if_interface_not_ok: true)(); }
    catch (\Throwable $e) { \NeoLibrary\NeoGlobal\throw_global_exception($e->getMessage(), status_code: 500, error_code: "neolibrary_clear_cache_error"); }
    return "OKAY";
});

\NeoLibrary\NeoGlobal\register_rest_endpoint("/wp-json/neo/library-get-id-from-url", "GET", fn () => \NeoLibrary\NeoGlobal\current_user_can__neo_library__view(), function ($get_param) {
    $img_url = $get_param("img-url");
    if (!is_string($img_url) || $img_url === "") { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Invalid image URL", "Ungültige Bild-URL"), status_code: 400, error_code: "neolibrary_get_id_from_url_invalid_request"); }
    $img_url = \NeoLibrary\NeoGlobal\percent_decode_invalid_utf8_url_bytes($img_url);
    $post_id = attachment_url_to_postid($img_url);
    if ($post_id === 0) { \NeoLibrary\NeoGlobal\throw_global_exception(\NeoLibrary\NeoGlobal\neo__("Image not found", "Bild nicht gefunden"), status_code: 404, error_code: "neolibrary_get_id_from_url_error"); }
    return ["postId" => $post_id];
});

\NeoLibrary\NeoGlobal\add_action_hook("attachment_submitbox_misc_actions:11", function() {
    $post_for_info = get_post();
    ?>
    <div class="misc-pub-section misc-pub--neo-library--id" data-upload-date-local="<?php echo esc_attr($post_for_info->post_date) ?>" data-upload-date-gmt="<?php echo esc_attr($post_for_info->post_date_gmt) ?>">
        <label for="neo-library--id">ID: </label><strong id="neo-library--id"><?php echo esc_html($post_for_info->ID) ?></strong>
    </div>
    <div class="misc-pub-section misc-pub--neo-library--slug">
        <label for="neo-library--slug">Slug: </label><strong id="neo-library--slug"><?php echo esc_html($post_for_info->post_name) ?></strong>
    </div>

    <?php \NeoLibrary\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
        import { observeOnce } from "<?php echo esc_url(\NeoLibrary\NeoGlobal\plugin_url()) ?>/_global--observer.js";

        observeOnce('label[for="attachment_alt"]',     (node) => node.innerHTML += `<p class="neo-library--explainer" title="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__('In the database as post meta entry "_wp_attachment_image_alt"', 'In der Datenbank als Post-Meta-Eintrag "_wp_attachment_image_alt"') ?>"><?php \NeoLibrary\NeoGlobal\echo_neo__("In the alt attribute of an image, not visible, important for SEO and accessibility",     "Im Img-Alt-Attribut, nicht sichtbar, wichtig für SEO und Barrierefreiheit") ?></p>`);
        observeOnce('label[for="attachment_caption"]', (node) => node.innerHTML += `<p class="neo-library--explainer" title="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__('In the database as post meta entry "_wp_attachment_metadata"',  'In der Datenbank als Post-Meta-Eintrag "_wp_attachment_metadata"') ?>" ><?php \NeoLibrary\NeoGlobal\echo_neo__("Visible in the frontend under the image as a caption (depending on the page builder)",   "Sichtbar im Frontend unter Bild als Bildbeschreibung (abhängig von Pagebuilder)") ?></p>`);
        observeOnce('label[for="attachment_content"]', (node) => node.innerHTML += `<p class="neo-library--explainer" title="<?php \NeoLibrary\NeoGlobal\echo_neo_attr__('In the database in the "post_content" column.',                 'In der Datenbank in der Post-Spalte "post_content"') ?>"               ><?php \NeoLibrary\NeoGlobal\echo_neo__("In the content of the image post, used by some widgets (depending on the page builder)", "Im Content des Bild-Post, von manchen Widgets verwendet (abhängig von Pagebuilder)") ?></p>`);
    <?php \NeoLibrary\NeoGlobal\backend_page_script_tag_end(); ?>
    <?php \NeoLibrary\NeoGlobal\backend_page_style_tag_start([]); ?>
        .neo-library--explainer { margin: 0; font-size: 0.9em; color: #666; }
        :where(label[for="attachment_alt"], label[for="attachment_caption"]):has(> p) + br { display: none; }
    <?php \NeoLibrary\NeoGlobal\backend_page_style_tag_end(); ?><?php
});
