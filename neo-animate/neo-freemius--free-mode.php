<?php
namespace NeoAnimate\NeoFreemius; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoAnimate\NeoGlobal\query_param("page") === \NeoAnimate\NeoGlobal\plugin_settings_page_slug())) { return; }
    if (!init_freemius_once_and_get_instance()->is_activation_mode()) { return; }

    foreach (\NeoAnimate\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) { if ($active_plugin["slug"] === "neo-media" || $active_plugin["slug"] === "neo-prod" || $active_plugin["edition"] === "pro" || $active_plugin["edition"] === "dev") { return; } }

    if (\NeoAnimate\NeoGlobal\query_param("require_license") === "false") { return; }
    wp_safe_redirect(\NeoAnimate\NeoGlobal\add_query_param(\NeoAnimate\NeoGlobal\request_uri(), "require_license", "false"));
    exit;
});
