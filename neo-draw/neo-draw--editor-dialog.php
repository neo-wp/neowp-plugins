<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_draw_editor_dialog_init_20250302() {
    \NeoDraw\NeoGlobal\enqueue_css("neo-draw--viewer-editable.css");
    \NeoDraw\NeoGlobal\enqueue_css("neo-draw--editor-dialog.css");
}
