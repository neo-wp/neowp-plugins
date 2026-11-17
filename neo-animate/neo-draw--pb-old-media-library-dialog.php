<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("load-post.php",     function () { if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return; } \NeoAnimate\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css"); });
\NeoAnimate\NeoGlobal\add_action_hook("load-post-new.php", function () { if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return; } \NeoAnimate\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css"); });

\NeoAnimate\NeoGlobal\add_filter_hook("media_upload_tabs:200", function ($tabs) { if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return $tabs; } return array_merge($tabs, ["neodraw" => \NeoAnimate\NeoGlobal\neo__("Create with neoDraw", "Mit neoDraw erstellen")]); });
\NeoAnimate\NeoGlobal\add_action_hook("media_upload_neodraw", function () {
    if (!\NeoAnimate\NeoGlobal\current_user_can__neo_draw()) { return; }
    \NeoAnimate\NeoGlobal\enqueue_js("neo-draw--pb-old-media-library-dialog.js");
    \NeoAnimate\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css");
    return wp_iframe(__NAMESPACE__ . "\\draw_pagebuilder_init");
});
