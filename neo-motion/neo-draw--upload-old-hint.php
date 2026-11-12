<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("current_screen", function ($hook) {
    if (!($hook instanceof \WP_Screen && $hook->base === "media" && $hook->action === "add")) { return; }
    [$neo_library_page_url, $interface_ok] = \NeoMotion\NeoGlobal\call_interface_func_implemented('\NeoMotion\NeoLibrary\interface_neo_library_menu_page_url_20250618')();
    if (!$interface_ok) { $neo_library_page_url = admin_url("admin.php?page=" . \NeoMotion\NeoGlobal\plugin_settings_page_slug()); }
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend("neoDrawUploadOldHintLibraryUrl", $neo_library_page_url);
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--upload-old-hint.js");
});

\NeoMotion\NeoGlobal\add_action_hook("print_media_templates", function () {
    if (!is_admin()) { return; }
    ?><?php \NeoMotion\NeoGlobal\backend_page_style_tag_start([]); ?>.media-frame .uploader-inline #post-upload-info::after { content: "Use the neoLibrary to upload neoDraw SVG files correctly."; }<?php \NeoMotion\NeoGlobal\backend_page_style_tag_end(); ?><?php 
});

if (str_starts_with(\NeoMotion\NeoGlobal\request_uri(), \NeoMotion\NeoGlobal\get_url_path(admin_url("async-upload.php")))) {
    \NeoMotion\NeoGlobal\add_filter_hook("gettext:1", function ($translated_text, $text, $domain) {
        if (isset($_SERVER["HTTP_X_NEO_LIBRARY"])) { return $translated_text; }
        if (!($text === "Sorry, you are not allowed to upload this file type.")) { return $translated_text; }
        return $translated_text . " ▶︎▶︎ " . \NeoMotion\NeoGlobal\neo__("Use the neoLibrary to upload neoDraw SVG files correctly.", "Verwende die neoLibrary, um neoDraw SVG-Dateien korrekt hochzuladen.") . " ◀︎◀︎";
    });
}
