<?php
namespace NeoLibrary\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoLibrary\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalPluginUrl",  \NeoLibrary\NeoGlobal\plugin_url());
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalUploadsUrl", \NeoLibrary\NeoGlobal\uploads_url());
});
