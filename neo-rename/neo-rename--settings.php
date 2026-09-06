<?php
namespace NeoRename\NeoRename; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {?>
        <neo-setting-neo-rename>
            <div slot="left">
                <h3><?php \NeoRename\NeoGlobal\echo_neo__("Clean up date subfolders", "Datums-Unterordner aufräumen") ?></h3>
                <p>
                    <?php \NeoRename\NeoGlobal\echo_neo__("Move images from the date folders to the /wp-content/uploads folder.", "Verschiebe Bilder aus den Datums-Ordnern in den /wp-content/uploads Ordner.")?><br>
                    <?php \NeoRename\NeoGlobal\echo_neo__("e.g. /uploads/" . esc_html(\NeoRename\NeoGlobal\wp_date_string("Y")) . "/12/image.jpg → /uploads/image.jpg", "z.B. /uploads/" . esc_html(\NeoRename\NeoGlobal\wp_date_string("Y")) . "/12/image.jpg → /uploads/image.jpg")?> 
                </p>
            </div>
            <div slot="right">
                <neo-button-neo-rename id="neo-rename--move-images-from-date-folders" only-enabled-if-pro><?php \NeoRename\NeoGlobal\echo_neo__("Move images (Preview)", "Bilder verschieben (Vorschau)") ?></neo-button-neo-rename>
            </div>
        </neo-setting-neo-rename>
        <?php \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoAi\interface_render_ai_settings_hint_20260802')();?>
    <?php };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_setting_20260326')("neo-rename", $settings_render_callback);

    $enqueue_assets_callback = function () { \NeoRename\NeoGlobal\enqueue_js("neo-rename--move-images-from-date-folders.js"); };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($enqueue_assets_callback);
});
