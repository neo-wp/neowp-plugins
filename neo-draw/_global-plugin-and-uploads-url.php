<?php
namespace NeoDraw\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalPluginUrl",  \NeoDraw\NeoGlobal\plugin_url());
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalUploadsUrl", \NeoDraw\NeoGlobal\uploads_url());
});
