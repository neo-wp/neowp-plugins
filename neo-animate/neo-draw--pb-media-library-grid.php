<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function draw_pagebuilder_init_grid() {
    \NeoAnimate\NeoGlobal\enqueue_js("neo-draw--pb-media-library-grid.js");
}
