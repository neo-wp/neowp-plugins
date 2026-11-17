<?php
namespace NeoDuplicate\NeoFreemius; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDuplicate\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoDuplicate\NeoGlobal\query_param("page") === \NeoDuplicate\NeoGlobal\plugin_settings_page_slug())) { return; }
    if (!init_freemius_once_and_get_instance()->is_activation_mode()) { return; }

    foreach (\NeoDuplicate\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) { if ($active_plugin["slug"] === "neo-media" || $active_plugin["slug"] === "neo-prod" || $active_plugin["edition"] === "pro" || $active_plugin["edition"] === "dev") { return; } }

    if (\NeoDuplicate\NeoGlobal\query_param("require_license") === "false") { return; }
    wp_safe_redirect(\NeoDuplicate\NeoGlobal\add_query_param(\NeoDuplicate\NeoGlobal\request_uri(), "require_license", "false"));
    exit;
});
