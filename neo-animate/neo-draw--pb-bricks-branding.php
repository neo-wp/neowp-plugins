<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

if (str_ends_with(get_template_directory(), "/bricks")) {
    \NeoAnimate\NeoGlobal\add_action_hook("wp_head", function () {
        if (\NeoAnimate\NeoGlobal\query_param("bricks") !== "run") { return; }
        if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return; }
        \NeoAnimate\NeoGlobal\enqueue_js("neo-draw--pb-bricks-branding.js");
        \NeoAnimate\NeoGlobal\enqueue_css("neo-draw--pb-bricks-branding.css");
    });
}
