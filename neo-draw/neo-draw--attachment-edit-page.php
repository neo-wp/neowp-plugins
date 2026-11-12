<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("current_screen", function () {
    if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }
    if ((get_current_screen()?->base ?? "") !== "post") { return; }
    if ((get_current_screen()?->post_type ?? "") !== "attachment") { return; }
    if (!\NeoDraw\NeoGlobal\is_neodraw_image_post(get_post(intval(\NeoDraw\NeoGlobal\query_param("post") ?? 0)))) { return; }
    interface_draw_editor_dialog_init_20250302();
    \NeoDraw\NeoGlobal\enqueue_js("neo-draw--attachment-edit-page.js");
});
