<?php
namespace NeoAlt\NeoAlt; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAlt\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {?>
        <neo-setting-neo-alt>
            <div slot="left">
                <h3><?php \NeoAlt\NeoGlobal\echo_neo__("Edit titles and alt texts", "Titel und Alt-Texte bearbeiten") ?></h3>
                <p><?php \NeoAlt\NeoGlobal\echo_neo__("Generate and review media titles and alt texts on the neoAlt tools page.", "Generiere und prüfe Medientitel und Alt-Texte auf der neoAlt Tools-Seite.") ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-alt href="<?php echo esc_url(admin_url("tools.php?page=" . page_slug())) ?>"><?php \NeoAlt\NeoGlobal\echo_neo__("Open neoAlt", "neoAlt öffnen") ?></neo-button-neo-alt>
            </div>
        </neo-setting-neo-alt><?php
        \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoAi\interface_render_ai_settings_hint_20260802')();
    };
    \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoSettings\interface_add_neo_setting_20260326')("neo-alt", $settings_render_callback);
});

\NeoAlt\NeoGlobal\add_action_hook("neo_init", function () {
    foreach (\NeoAlt\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        if (!in_array($active_plugin["slug"], ["neo-alt", "neo-media"], true)) { continue; }
        \NeoAlt\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]) . ":15", function ($links) {
            array_unshift($links, '<a href="' . esc_url(admin_url("tools.php?page=" . page_slug())) . '">' . esc_html(\NeoAlt\NeoGlobal\neo__("Open neoAlt", "neoAlt öffnen")) . '</a>');
            return $links;
        });
    }
});
