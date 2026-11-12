<?php
namespace NeoDraw\NeoMotion; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("wp_enqueue_scripts", function () { \NeoDraw\NeoGlobal\enqueue_js("neo-motion--frontend-reload-stopmotion-image-in-viewport.frontend.js"); });
\NeoDraw\NeoGlobal\frontend_image_hook_register_callback(function ($get_attr, $set_attr, $image_file_path) {
    if (!\NeoDraw\NeoGlobal\is_neodraw_image_path($image_file_path)) { return; }
    $neodraw_metadata = \NeoDraw\NeoGlobal\get_image_metadata_by_path($image_file_path); if ($neodraw_metadata === false) { return; }
    $set_attr("src", \NeoDraw\NeoGlobal\add_or_update_query_params($get_attr("src"), ["neo-motion" => ($neodraw_metadata["isMotion"] ?? false) ? "true" : "false"]));
});
\NeoDraw\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {
        ?><neo-setting-neo-draw>
            <div slot="left">
                <h3><?php \NeoDraw\NeoGlobal\echo_neo__("Create stop-motion graphic", "Stop-Motion Grafik erstellen") ?></h3>
                <p><?php \NeoDraw\NeoGlobal\echo_neo__("Start with a prepared neoMotion template to create a stop-motion animation yourself. Preview in the editor via the play button at the top right.", "Starte mit einer vorbereiteten neoMotion-Vorlage, um eine Stop-Motion-Animation selbst zu erstellen. Vorschau im Editor über den Play-Button oben rechts.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-draw class="neo-motion--create-starter-button"><?php \NeoDraw\NeoGlobal\echo_neo__("Create new", "Neu erstellen"); ?></neo-button-neo-draw>
            </div>
        </neo-setting-neo-draw>
        <?php
    };
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoSettings\interface_add_neo_setting_20260326')("neo-motion", $settings_render_callback);
    $assets_callback = function () { \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoDraw\interface_draw_editor_dialog_init_20250302')(); \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoMotionStarterImageUrl", "data:image/svg+xml;base64," . base64_encode(\NeoDraw\NeoGlobal\fs_file_get_contents(\NeoDraw\NeoGlobal\plugin_path() . "/img/neo-motion--starter-image.svg"))); \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoMotionMediaLibraryUrl", admin_url("upload.php")); \NeoDraw\NeoGlobal\enqueue_js("neo-motion--settings.js"); };
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
    [$neo_motion_settings_section_url, $interface_ok] = \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-motion"); if (!$interface_ok) { return; }
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-motion", $neo_motion_settings_section_url);
    foreach (\NeoDraw\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (!in_array($active_plugin["slug"], ["neo-motion", "neo-media"], true)) { continue; }
        \NeoDraw\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]) . ":12", function ($links) {
            [$media_library_url, $neo_library_interface_ok] = \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoLibrary\interface_neo_library_menu_page_url_20250618')(); if (!$neo_library_interface_ok) { $media_library_url = admin_url("upload.php"); }
            array_unshift($links, '<a href="' . esc_url(\NeoDraw\NeoGlobal\add_or_update_query_params($media_library_url, ["neo-motion--create" => "true"])) . '">' . esc_html(\NeoDraw\NeoGlobal\neo__("Create neoMotion animation", "neoMotion-Animation erstellen")) . '</a>');
            return $links;
        });
    }
});

\NeoDraw\NeoGlobal\add_action_hook("current_screen", function () {
    global $pagenow;
    if ($pagenow !== "upload.php" || \NeoDraw\NeoGlobal\query_param("neo-motion--create") !== "true") { return; }
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoDraw\interface_draw_editor_dialog_init_20250302')();
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoMotionStarterImageUrl", "data:image/svg+xml;base64," . base64_encode(\NeoDraw\NeoGlobal\fs_file_get_contents(\NeoDraw\NeoGlobal\plugin_path() . "/img/neo-motion--starter-image.svg")));
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoMotionMediaLibraryUrl", admin_url("upload.php"));
    \NeoDraw\NeoGlobal\enqueue_js("neo-motion--settings.js");
});
function interface_get_neo_draw_editor_head_html_20260615() {
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoMotionStarterImageUrl", "data:image/svg+xml;base64," . base64_encode(\NeoDraw\NeoGlobal\fs_file_get_contents(\NeoDraw\NeoGlobal\plugin_path() . "/img/neo-motion--starter-image.svg")));
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoMotionShowDrawStarterHint", \NeoDraw\NeoGlobal\plugin_slug() === "neo-motion" && count(\NeoDraw\NeoGlobal\array_filter_better(\NeoDraw\NeoEntrypoint\get_neo_active_plugins(), fn ($active_neo_plugin) => $active_neo_plugin["slug"] === "neo-draw")) === 0);
    return \NeoDraw\NeoGlobal\get_code_for_js_variables("backend");
}
function interface_get_neo_draw_editor_web_component_importer_url_20260615() {
    return \NeoDraw\NeoGlobal\plugin_url() . "/_global-web-component-importer.js";
}
