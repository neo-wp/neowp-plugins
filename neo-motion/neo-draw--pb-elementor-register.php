<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("elementor/editor/before_enqueue_scripts", function () {
    if (!defined("ELEMENTOR_VERSION") || version_compare(ELEMENTOR_VERSION, "2.9.14", "<")) {
        return;
    }
    if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; }
    draw_pagebuilder_init();
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--pb-elementor-control.js");
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--pb-elementor-atomic-image.js");
    \NeoMotion\NeoGlobal\enqueue_css("neo-draw--pb-elementor-control.css");
    \NeoMotion\NeoGlobal\enqueue_css("neo-draw--pb-elementor-atomic-image.css");
});
