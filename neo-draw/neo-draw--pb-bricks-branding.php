<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

if (str_ends_with(get_template_directory(), "/bricks")) {
    \NeoDraw\NeoGlobal\add_action_hook("wp_head", function () {
        if (\NeoDraw\NeoGlobal\query_param("bricks") !== "run") { return; }
        if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }
        \NeoDraw\NeoGlobal\enqueue_js("neo-draw--pb-bricks-branding.js");
        \NeoDraw\NeoGlobal\enqueue_css("neo-draw--pb-bricks-branding.css");

        \NeoDraw\NeoGlobal\enqueue_js("neo-draw--pb-bricks-media-browser.js");
        \NeoDraw\NeoGlobal\enqueue_css("neo-draw--pb-bricks-media-browser.css");
        interface_draw_editor_dialog_init_20250302();
    });
}
