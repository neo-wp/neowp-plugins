<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function draw_pagebuilder_init_classic() {
    if (!class_exists("Classic_Editor")) { return; }
    if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; }

    draw_pagebuilder_init();

    \NeoMotion\NeoGlobal\add_filter_hook("mce_external_plugins", function ($plugin_array) { return array_merge($plugin_array, ["neo_draw__mce_button" => \NeoMotion\NeoGlobal\plugin_url() . "/neo-draw--pb-classic-button-visual.js"]); });
    \NeoMotion\NeoGlobal\add_filter_hook("mce_buttons", function ($buttons) { return array_merge($buttons, ["neo_draw__mce_button"]); });
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--pb-classic-button-text.js", dependencies: ["quicktags"]);
}
\NeoMotion\NeoGlobal\add_action_hook("load-post.php",     function () { draw_pagebuilder_init_classic(); });
\NeoMotion\NeoGlobal\add_action_hook("load-post-new.php", function () { draw_pagebuilder_init_classic(); });
