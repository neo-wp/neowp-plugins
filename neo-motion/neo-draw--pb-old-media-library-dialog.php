<?php
namespace NeoMotion\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("load-post.php",     function () { if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; } \NeoMotion\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css"); });
\NeoMotion\NeoGlobal\add_action_hook("load-post-new.php", function () { if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; } \NeoMotion\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css"); });

\NeoMotion\NeoGlobal\add_filter_hook("media_upload_tabs:200", function ($tabs) { if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return $tabs; } return array_merge($tabs, ["neodraw" => \NeoMotion\NeoGlobal\neo__("Create with neoDraw", "Mit neoDraw erstellen")]); });
\NeoMotion\NeoGlobal\add_action_hook("media_upload_neodraw", function () {
    if (!\NeoMotion\NeoGlobal\current_user_can__neo_draw()) { return; }
    \NeoMotion\NeoGlobal\enqueue_js("neo-draw--pb-old-media-library-dialog.js");
    \NeoMotion\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css");
    return wp_iframe(__NAMESPACE__ . "\\draw_pagebuilder_init");
});
