<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("enqueue_block_editor_assets", function () {
    if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }
    draw_pagebuilder_init();
    \NeoDraw\NeoGlobal\enqueue_js("neo-draw--pb-gutenberg-block.js", dependencies: ["wp-hooks", "wp-blocks", "wp-element", "wp-block-editor", "wp-components"]);
});
\NeoDraw\NeoGlobal\add_action_hook("enqueue_block_assets", function () {
    if (!is_admin()) { return; }
    if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }

    \NeoDraw\NeoGlobal\enqueue_css("neo-draw--pb-gutenberg-block.css");
});
