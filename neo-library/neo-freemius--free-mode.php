<?php
namespace NeoLibrary\NeoFreemius; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoLibrary\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoLibrary\NeoGlobal\query_param("page") === \NeoLibrary\NeoGlobal\plugin_settings_page_slug())) { return; }
    if (!init_freemius_once_and_get_instance()->is_activation_mode()) { return; }

    foreach (\NeoLibrary\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) { if ($active_plugin["slug"] === "neo-media" || $active_plugin["slug"] === "neo-prod" || $active_plugin["edition"] === "pro" || $active_plugin["edition"] === "dev") { return; } }

    if (\NeoLibrary\NeoGlobal\query_param("require_license") === "false") { return; }
    wp_safe_redirect(\NeoLibrary\NeoGlobal\add_query_param(\NeoLibrary\NeoGlobal\request_uri(), "require_license", "false"));
    exit;
});
