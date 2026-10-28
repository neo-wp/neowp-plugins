<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoRename\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalPluginUrl",  \NeoRename\NeoGlobal\plugin_url());
    \NeoRename\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalUploadsUrl", \NeoRename\NeoGlobal\uploads_url());
});
