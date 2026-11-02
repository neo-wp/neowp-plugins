<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

if (\NeoDraw\NeoGlobal\option__neo_draw__svg_upload_enabled()) {
    interface_enable_svg_uploads_20250302();
}

\NeoDraw\NeoGlobal\add_action_hook("neo_init", function () {
    $create_settings_render_callback = function () {
        [$media_library_url, $neo_library_interface_ok] = \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoLibrary\interface_neo_library_menu_page_url_20250618')(); if (!$neo_library_interface_ok) { $media_library_url = admin_url("upload.php"); }
        ?><neo-setting-neo-draw>
            <div slot="left">
                <h3><?php \NeoDraw\NeoGlobal\echo_neo__("Create neoDraw", "neoDraw erstellen") ?></h3>
                <p><?php \NeoDraw\NeoGlobal\echo_neo__("Create a new neoDraw drawing directly in the media library.", "Erstelle eine neue neoDraw-Zeichnung direkt in der Mediathek.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-draw href="<?php echo esc_url(\NeoDraw\NeoGlobal\add_or_update_query_params($media_library_url, ["neo-draw--create" => "true"])) ?>"><?php \NeoDraw\NeoGlobal\echo_neo__("Create neoDraw", "neoDraw erstellen") ?></neo-button-neo-draw>
            </div>
        </neo-setting-neo-draw><?php
    };
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoSettings\interface_add_neo_setting_20260326')("neo-draw", $create_settings_render_callback, show_to_editor: true);
    $settings_render_callback = function () {
        ?><neo-setting-neo-draw>
            <div slot="left">
                <h3><?php \NeoDraw\NeoGlobal\echo_neo__("SVG uploads", "SVG-Uploads") ?></h3>
                <p><?php \NeoDraw\NeoGlobal\echo_neo__("Enable SVG uploads for neoDraw drawings in the classic WP uploader.", "SVG-Uploads von neoDraw-Zeichnungen im klassischen WP-Upload aktivieren.") ?></p>
            </div>
            <div slot="right">
                <?php \NeoDraw\NeoGlobal\echo_switch_for_option("neo_draw__svg_upload_enabled", \NeoDraw\NeoGlobal\neo__("SVG uploads", "SVG-Uploads")) ?> 
            </div>
        </neo-setting-neo-draw><?php
    };
    \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoSettings\interface_add_neo_setting_20260326')("neo-draw", $settings_render_callback);
});
