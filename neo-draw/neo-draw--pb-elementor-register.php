<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("elementor/editor/before_enqueue_scripts", function () {
    if (!defined("ELEMENTOR_VERSION") || version_compare(ELEMENTOR_VERSION, "2.9.14", "<")) {
        return;
    }
    if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }
    draw_pagebuilder_init();
    \NeoDraw\NeoGlobal\enqueue_js("neo-draw--pb-elementor-control.js");
    \NeoDraw\NeoGlobal\enqueue_js("neo-draw--pb-elementor-atomic-image.js");
    \NeoDraw\NeoGlobal\enqueue_css("neo-draw--pb-elementor-control.css");
    \NeoDraw\NeoGlobal\enqueue_css("neo-draw--pb-elementor-atomic-image.css");
});
