<?php
namespace NeoMotion\NeoMotion; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("wp_enqueue_scripts", function () { \NeoMotion\NeoGlobal\enqueue_js("neo-motion--frontend-reload-stopmotion-image-in-viewport.frontend.js"); });
\NeoMotion\NeoGlobal\frontend_image_hook_register_callback(function ($get_attr, $set_attr, $image_file_path) {
    if (!\NeoMotion\NeoGlobal\is_neodraw_image_path($image_file_path)) { return; }
    $neodraw_metadata = \NeoMotion\NeoGlobal\get_image_metadata_by_path($image_file_path); if ($neodraw_metadata === false) { return; }
    $set_attr("src", \NeoMotion\NeoGlobal\add_or_update_query_params($get_attr("src"), ["neo-motion" => ($neodraw_metadata["isMotion"] ?? false) ? "true" : "false"]));
});
\NeoMotion\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {
        ?><neo-setting-neo-motion>
            <div slot="left">
                <h3><?php \NeoMotion\NeoGlobal\echo_neo__("Create stop-motion graphic", "Stop-Motion Grafik erstellen") ?></h3>
                <p><?php \NeoMotion\NeoGlobal\echo_neo__("Start with a prepared neoMotion template to create a stop-motion animation yourself. Preview in the editor via the play button at the top right.", "Starte mit einer vorbereiteten neoMotion-Vorlage, um eine Stop-Motion-Animation selbst zu erstellen. Vorschau im Editor über den Play-Button oben rechts.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-motion class="neo-motion--create-starter-button"><?php \NeoMotion\NeoGlobal\echo_neo__("Create new", "Neu erstellen"); ?></neo-button-neo-motion>
            </div>
        </neo-setting-neo-motion>
        <?php
    };
    \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoSettings\interface_add_neo_setting_20260326')("neo-motion", $settings_render_callback);
    $assets_callback = function () { \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoDraw\interface_draw_editor_dialog_init_20250302')(); \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoMotionStarterImageUrl", "data:image/svg+xml;base64," . base64_encode(\NeoMotion\NeoGlobal\fs_file_get_contents(\NeoMotion\NeoGlobal\plugin_path() . "/img/neo-motion--starter-image.svg"))); \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoMotionMediaLibraryUrl", admin_url("upload.php")); \NeoMotion\NeoGlobal\enqueue_js("neo-motion--settings.js"); };
    \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
    [$neo_motion_settings_section_url, $interface_ok] = \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-motion"); if (!$interface_ok) { return; }
    \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoPlayground\interface_run_plugin_demo_redirect_20260604')("neo-motion", $neo_motion_settings_section_url);
    foreach (\NeoMotion\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (!in_array($active_plugin["slug"], ["neo-motion", "neo-media"], true)) { continue; }
        \NeoMotion\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]) . ":12", function ($links) {
            [$media_library_url, $neo_library_interface_ok] = \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoLibrary\interface_neo_library_menu_page_url_20250618')(); if (!$neo_library_interface_ok) { $media_library_url = admin_url("upload.php"); }
            array_unshift($links, '<a href="' . esc_url(\NeoMotion\NeoGlobal\add_or_update_query_params($media_library_url, ["neo-motion--create" => "true"])) . '">' . esc_html(\NeoMotion\NeoGlobal\neo__("Create neoMotion animation", "neoMotion-Animation erstellen")) . '</a>');
            return $links;
        });
    }
});

\NeoMotion\NeoGlobal\add_action_hook("current_screen", function () {
    global $pagenow;
    if ($pagenow !== "upload.php" || \NeoMotion\NeoGlobal\query_param("neo-motion--create") !== "true") { return; }
    \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoDraw\interface_draw_editor_dialog_init_20250302')();
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoMotionStarterImageUrl", "data:image/svg+xml;base64," . base64_encode(\NeoMotion\NeoGlobal\fs_file_get_contents(\NeoMotion\NeoGlobal\plugin_path() . "/img/neo-motion--starter-image.svg")));
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoMotionMediaLibraryUrl", admin_url("upload.php"));
    \NeoMotion\NeoGlobal\enqueue_js("neo-motion--settings.js");
});
function interface_get_neo_draw_editor_head_html_20260615() {
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoMotionStarterImageUrl", "data:image/svg+xml;base64," . base64_encode(\NeoMotion\NeoGlobal\fs_file_get_contents(\NeoMotion\NeoGlobal\plugin_path() . "/img/neo-motion--starter-image.svg")));
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoMotionShowDrawStarterHint", \NeoMotion\NeoGlobal\plugin_slug() === "neo-motion" && count(\NeoMotion\NeoGlobal\array_filter_better(\NeoMotion\NeoEntrypoint\get_neo_active_plugins(), fn ($active_neo_plugin) => $active_neo_plugin["slug"] === "neo-draw")) === 0);
    return \NeoMotion\NeoGlobal\get_code_for_js_variables("backend");
}
function interface_get_neo_draw_editor_web_component_importer_url_20260615() {
    return \NeoMotion\NeoGlobal\plugin_url() . "/_global-web-component-importer.js";
}
