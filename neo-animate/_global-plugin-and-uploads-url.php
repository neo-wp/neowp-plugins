<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalPluginUrl",  \NeoAnimate\NeoGlobal\plugin_url());
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalUploadsUrl", \NeoAnimate\NeoGlobal\uploads_url());
});
