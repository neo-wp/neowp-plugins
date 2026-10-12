<?php
namespace NeoMotion\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalPluginUrl",  \NeoMotion\NeoGlobal\plugin_url());
    \NeoMotion\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalUploadsUrl", \NeoMotion\NeoGlobal\uploads_url());
});
