<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("enqueue_block_editor_assets", function () {
    if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; }
    draw_pagebuilder_init();
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--pb-gutenberg-block.js", dependencies: ["wp-hooks", "wp-blocks", "wp-element", "wp-block-editor", "wp-components"]);
});
\NeoMotion\NeoGlobal\add_action_hook("enqueue_block_assets", function () {
    if (!is_admin()) { return; }
    if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; }

    \NeoMotion\NeoGlobal\enqueue_css("neo-draw--pb-gutenberg-block.css");
});
