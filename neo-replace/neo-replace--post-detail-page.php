<?php
namespace NeoReplace\NeoReplace; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    if ((get_current_screen()?->base ?? "") !== "post") { return; }
    if ((get_current_screen()?->post_type ?? "") !== "attachment") { return; }
    if (!\NeoReplace\NeoGlobal\current_user_can__neo_replace()) { return; }
    $img_url = wp_get_attachment_url(intval(\NeoReplace\NeoGlobal\query_param("post") ?? 0)); if (!$img_url) { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoReplacePostDetailImgUrl", \NeoReplace\NeoGlobal\percent_encode_invalid_utf8_url_bytes($img_url));
});
\NeoReplace\NeoGlobal\add_action_hook("edit_form_advanced", function () {
    if (get_post_type() !== "attachment") { return; }
    if (!\NeoReplace\NeoGlobal\current_user_can__neo_replace()) { return; }
    \NeoReplace\NeoGlobal\enqueue_js("neo-replace--post-detail-page.js");
});
