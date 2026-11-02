<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAlt\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalPluginUrl",  \NeoAlt\NeoGlobal\plugin_url());
    \NeoAlt\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalUploadsUrl", \NeoAlt\NeoGlobal\uploads_url());
});
