<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("enqueue_block_editor_assets", function () {
    if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return; }
    draw_pagebuilder_init();
    \NeoAnimate\NeoGlobal\enqueue_js("neo-draw--pb-gutenberg-block.js", dependencies: ["wp-hooks", "wp-blocks", "wp-element", "wp-block-editor", "wp-components"]);
});
\NeoAnimate\NeoGlobal\add_action_hook("enqueue_block_assets", function () {
    if (!is_admin()) { return; }
    if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return; }

    \NeoAnimate\NeoGlobal\enqueue_css("neo-draw--pb-gutenberg-block.css");
});
