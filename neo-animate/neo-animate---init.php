<?php
namespace NeoAnimate\NeoAnimate; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("wp_head", function () {
    ?>
    <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_start([]); ?>
        img[src*="neo-animate--animated=true"]:not(.neo-animate--suppress-flash-prevention) { --neo-animate--visibility: hidden; visibility: var(--neo-animate--visibility) !important;}

        .neo-animate--unpacked-svg * { animation-iteration-count: 1!important; }

        @keyframes showElement { from { --neo-animate--visibility: hidden; } to { --neo-animate--visibility: visible; } };
        img[src*="neo-animate--animated=true"]:not(.neo-animate--suppress-flash-prevention) { animation: showElement 0.1s forwards 20s; }
    <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_end(); ?><?php
});
\NeoAnimate\NeoGlobal\frontend_image_hook_register_callback(function ($get_attr, $set_attr, $image_file_path) {
    if (!\NeoAnimate\NeoGlobal\is_neodraw_image_path($image_file_path)) { return; }
    $neodraw_metadata = \NeoAnimate\NeoGlobal\get_image_metadata_by_path($image_file_path); if ($neodraw_metadata === false) { return; }
    $set_attr("src", \NeoAnimate\NeoGlobal\add_or_update_query_params($get_attr("src"), ["neo-animate--animated" => ($neodraw_metadata["isAnimated"] ?? false) ? "true" : "false"]));
});
\NeoAnimate\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoAnimatePluginVersion", \NeoAnimate\NeoGlobal\plugin_version());
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoAnimateEditorUrl", \NeoAnimate\NeoGlobal\get_backend_page_url("neo-animate--editor"));
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-animate", \NeoAnimate\NeoGlobal\add_or_update_query_params(admin_url("upload.php"), ["mode" => "list", "force-old-media-lib" => "true"]));
    $create_settings_render_callback = function () {
        [$media_library_url, $neo_library_interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoLibrary\interface_neo_library_menu_page_url_20250618')(); if (!$neo_library_interface_ok) { $media_library_url = admin_url("upload.php"); }
        $create_url = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoDraw\interface_get_create_url_20260824', throw_if_interface_not_ok: true)($media_library_url);
        ?><neo-setting-neo-animate>
            <div slot="left">
                <h3><?php \NeoAnimate\NeoGlobal\echo_neo__("Create new", "Neu erstellen") ?></h3>
                <p><?php \NeoAnimate\NeoGlobal\echo_neo__("Create a new neoAnimate animation directly in the media library.", "Erstelle eine neue neoAnimate-Animation direkt in der Mediathek.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-animate href="<?php echo esc_url($create_url) ?>"><?php \NeoAnimate\NeoGlobal\echo_neo__("Create new", "Neu erstellen") ?></neo-button-neo-animate>
            </div>
        </neo-setting-neo-animate><?php
    };
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoSettings\interface_add_neo_setting_20260326')("neo-animate", $create_settings_render_callback, show_to_editor: true);

    foreach (\NeoAnimate\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (!in_array($active_plugin["slug"], ["neo-animate", "neo-media"], true)) { continue; }
        \NeoAnimate\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]) . ":13", function ($links) {
            [$media_library_url, $neo_library_interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoLibrary\interface_neo_library_menu_page_url_20250618')(); if (!$neo_library_interface_ok) { $media_library_url = admin_url("upload.php"); }
            $create_url = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoDraw\interface_get_create_url_20260824', throw_if_interface_not_ok: true)($media_library_url);
            array_unshift($links, '<a href="' . esc_url($create_url) . '">' . esc_html(\NeoAnimate\NeoGlobal\neo__("Create neoAnimate animation", "neoAnimate-Animation erstellen")) . '</a>');
            return $links;
        });
    }
});
function interface_get_neo_draw_editor_head_html_20260611() {
    return \NeoAnimate\NeoGlobal\get_code_for_js_variables("backend");
}
function interface_get_neo_draw_editor_web_component_importer_url_20260615() {
    return \NeoAnimate\NeoGlobal\plugin_url() . "/_global-web-component-importer.js";
}
