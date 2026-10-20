<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("current_screen", function () {
    if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; }
    if ((get_current_screen()?->base ?? "") !== "post") { return; }
    if ((get_current_screen()?->post_type ?? "") !== "attachment") { return; }
    if (!\NeoMotion\NeoGlobal\is_neodraw_image_post(get_post(intval(\NeoMotion\NeoGlobal\query_param("post") ?? 0)))) { return; }
    interface_draw_editor_dialog_init_20250302();
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--attachment-edit-page.js");
});
