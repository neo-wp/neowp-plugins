<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

if (str_ends_with(get_template_directory(), "/bricks")) {
    \NeoMotion\NeoGlobal\add_action_hook("wp_head", function () {
        if (\NeoMotion\NeoGlobal\query_param("bricks") !== "run") { return; }
        if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; }
        \NeoMotion\NeoGlobal\enqueue_js("neo-draw--pb-bricks-branding.js");
        \NeoMotion\NeoGlobal\enqueue_css("neo-draw--pb-bricks-branding.css");

        \NeoMotion\NeoGlobal\enqueue_js("neo-draw--pb-bricks-media-browser.js");
        \NeoMotion\NeoGlobal\enqueue_css("neo-draw--pb-bricks-media-browser.css");
        interface_draw_editor_dialog_init_20250302();
    });
}
