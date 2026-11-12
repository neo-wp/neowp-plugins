<?php
namespace NeoRename\NeoRename; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\add_action_hook("admin_footer", function () {
    global $pagenow; if ($pagenow !== "upload.php") { return; }
    if (!\NeoRename\NeoGlobal\current_user_can__neo_rename()) { return; }
    \NeoRename\NeoGlobal\enqueue_js("neo-rename--bulk.js");
});
