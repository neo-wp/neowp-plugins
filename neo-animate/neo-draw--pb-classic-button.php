<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function draw_pagebuilder_init_classic() {
    if (!class_exists("Classic_Editor")) { return; }
    if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return; }

    draw_pagebuilder_init();

    \NeoAnimate\NeoGlobal\add_filter_hook("mce_external_plugins", function ($plugin_array) { return array_merge($plugin_array, ["neo_draw__mce_button" => \NeoAnimate\NeoGlobal\plugin_url() . "/neo-draw--pb-classic-button-visual.js"]); });
    \NeoAnimate\NeoGlobal\add_filter_hook("mce_buttons", function ($buttons) { return array_merge($buttons, ["neo_draw__mce_button"]); });
    \NeoAnimate\NeoGlobal\enqueue_js("neo-draw--pb-classic-button-text.js", dependencies: ["quicktags"]);
}
\NeoAnimate\NeoGlobal\add_action_hook("load-post.php",     function () { draw_pagebuilder_init_classic(); });
\NeoAnimate\NeoGlobal\add_action_hook("load-post-new.php", function () { draw_pagebuilder_init_classic(); });
