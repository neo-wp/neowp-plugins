<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

if (\NeoAnimate\NeoGlobal\option__neo_draw__svg_upload_enabled()) {
    interface_enable_svg_uploads_20250302();
}

\NeoAnimate\NeoGlobal\add_action_hook("neo_init", function () {
    $create_settings_render_callback = function () {
        [$media_library_url, $neo_library_interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoLibrary\interface_neo_library_menu_page_url_20250618')(); if (!$neo_library_interface_ok) { $media_library_url = admin_url("upload.php"); }
        ?><neo-setting-neo-animate>
            <div slot="left">
                <h3><?php \NeoAnimate\NeoGlobal\echo_neo__("Create neoDraw", "neoDraw erstellen") ?></h3>
                <p><?php \NeoAnimate\NeoGlobal\echo_neo__("Create a new neoDraw drawing directly in the media library.", "Erstelle eine neue neoDraw-Zeichnung direkt in der Mediathek.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-animate href="<?php echo esc_url(\NeoAnimate\NeoGlobal\add_or_update_query_params($media_library_url, ["neo-draw--create" => "true"])) ?>"><?php \NeoAnimate\NeoGlobal\echo_neo__("Create neoDraw", "neoDraw erstellen") ?></neo-button-neo-animate>
            </div>
        </neo-setting-neo-animate><?php
    };
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoSettings\interface_add_neo_setting_20260326')("neo-draw", $create_settings_render_callback, show_to_editor: true);
    $settings_render_callback = function () {
        ?><neo-setting-neo-animate>
            <div slot="left">
                <h3><?php \NeoAnimate\NeoGlobal\echo_neo__("SVG uploads", "SVG-Uploads") ?></h3>
                <p><?php \NeoAnimate\NeoGlobal\echo_neo__("Enable SVG uploads for neoDraw drawings in the classic WP uploader.", "SVG-Uploads von neoDraw-Zeichnungen im klassischen WP-Upload aktivieren.") ?></p>
            </div>
            <div slot="right">
                <?php \NeoAnimate\NeoGlobal\echo_switch_for_option("neo_draw__svg_upload_enabled", \NeoAnimate\NeoGlobal\neo__("SVG uploads", "SVG-Uploads")) ?> 
            </div>
        </neo-setting-neo-animate><?php
    };
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoSettings\interface_add_neo_setting_20260326')("neo-draw", $settings_render_callback);
});
