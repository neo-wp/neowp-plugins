<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDuplicate\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalPluginUrl",  \NeoDuplicate\NeoGlobal\plugin_url());
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalUploadsUrl", \NeoDuplicate\NeoGlobal\uploads_url());
});
