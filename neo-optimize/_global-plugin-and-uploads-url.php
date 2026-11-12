<?php
namespace NeoOptimize\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalPluginUrl",  \NeoOptimize\NeoGlobal\plugin_url());
    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalUploadsUrl", \NeoOptimize\NeoGlobal\uploads_url());
});
