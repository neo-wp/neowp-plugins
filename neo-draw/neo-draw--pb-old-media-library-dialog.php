<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("load-post.php",     function () { if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; } \NeoDraw\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css"); });
\NeoDraw\NeoGlobal\add_action_hook("load-post-new.php", function () { if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; } \NeoDraw\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css"); });

\NeoDraw\NeoGlobal\add_filter_hook("media_upload_tabs:200", function ($tabs) { if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return $tabs; } return array_merge($tabs, ["neodraw" => \NeoDraw\NeoGlobal\neo__("Create with neoDraw", "Mit neoDraw erstellen")]); });
\NeoDraw\NeoGlobal\add_action_hook("media_upload_neodraw", function () {
    if (!\NeoDraw\NeoGlobal\current_user_can__neo_draw()) { return; }
    \NeoDraw\NeoGlobal\enqueue_js("neo-draw--pb-old-media-library-dialog.js");
    \NeoDraw\NeoGlobal\enqueue_css("neo-draw--pb-old-media-library-dialog.css");
    return wp_iframe(__NAMESPACE__ . "\\draw_pagebuilder_init");
});
