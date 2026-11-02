<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function draw_pagebuilder_init() {
    if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }

    interface_draw_editor_dialog_init_20250302();
}
