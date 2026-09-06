<?php
namespace NeoAlt\NeoManagerWporg; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_neo_manager_render_settings_card_slot_20260327($plugin_data, $setting_slot) {
    if ($setting_slot === "switch") {
        if (in_array(\NeoAlt\NeoGlobal\get_plugin_entry_path_relative($plugin_data["plugin-slug"]), array_keys(get_plugins())) || \NeoAlt\NeoGlobal\is_module_available($plugin_data["plugin-slug"])) { return; }
        if (!$plugin_data["plugin-is-coming-soon"]) {
            if (!empty($plugin_data["plugin-wporg-url"]) && \NeoAlt\NeoGlobal\current_user_can__neo_manager_wporg()) {?>
                <neo-button-neo-alt class="neo-manager-wporg--install-button" href="<?php echo esc_url(self_admin_url("plugin-install.php?tab=plugin-information&plugin=" . $plugin_data["plugin-slug"] . "&TB_iframe=true&width=600&height=550")) ?>"><?php \NeoAlt\NeoGlobal\echo_neo__("Install", "Installieren") ?></neo-button-neo-alt>
            <?php } else { ?>
                <neo-button-neo-alt href="<?php echo esc_url(\NeoAlt\NeoGlobal\get_plugin_download_zip_file_url_without_version($plugin_data["plugin-slug"], edition: "full")) ?>" target="_blank">
                    <?php $plugin_data["plugin-slug"] === "neo-plugins" ? \NeoAlt\NeoGlobal\echo_neo__("Download all neoPlugins", "Download aller neoPlugins") : \NeoAlt\NeoGlobal\echo_neo__("Download ZIP", "Download ZIP")?> 
                </neo-button-neo-alt><?php
            }
        } else if (!empty($plugin_data["plugin-website-url"])) {
            ?><neo-button-neo-alt href="<?php echo esc_url($plugin_data["plugin-website-url"]) ?>" target="_blank"><?php \NeoAlt\NeoGlobal\echo_neo__("More Info", "Weitere Infos") ?></neo-button-neo-alt><?php
        }
    } else if ($setting_slot === "tooltip") {
        ?><neo-info-tooltip-neo-alt>
            <div style="text-align: left;">
                <ul style="list-style-type: none; padding-left: 0; margin: 0;">
                    <li><a href="<?php echo esc_url($plugin_data["plugin-website-url"]     ?? "") ?>" target="_blank" rel="noopener"><?php \NeoAlt\NeoGlobal\echo_neo__("Website", "Webseite")        ?></a></li>
                    <?php if (!empty($plugin_data["plugin-wporg-url"])) { ?><li><a href="<?php echo esc_url($plugin_data["plugin-wporg-url"]) ?>" target="_blank" rel="noopener"><?php \NeoAlt\NeoGlobal\echo_neo__("WordPress.org plugin page", "WordPress.org-Pluginseite") ?></a></li><?php } ?> 
                    <li><a href="<?php echo esc_url($plugin_data["plugin-screenshots-url"] ?? "") ?>" target="_blank" rel="noopener"><?php \NeoAlt\NeoGlobal\echo_neo__("Screenshots", "Screenshots") ?></a></li>
                    <li><a href="<?php echo esc_url("https://" . \NeoAlt\NeoGlobal\option__neo_wp_com() . \NeoAlt\NeoGlobal\neo__("", "/de") . "/install-wordpress-plugin-zip/?ref=neo-manager-wporg") ?>" target="_blank" rel="noopener"><?php \NeoAlt\NeoGlobal\echo_neo__("Installation instructions", "Installationsanleitung") ?></a></li>
                    <?php if (!empty($plugin_data["plugin-wporg-url"])) { ?><li><a href="<?php echo esc_url(\NeoAlt\NeoGlobal\get_plugin_download_zip_file_url_without_version($plugin_data["plugin-slug"], edition: "full")) ?>" target="_blank" rel="noopener"><?php \NeoAlt\NeoGlobal\echo_neo__("Download ZIP", "Download ZIP") ?></a></li><?php } ?> 
                </ul>
            </div>
        </neo-info-tooltip-neo-alt><?php
    } else if ($setting_slot === "install-options") { }
    else if ($setting_slot === "reload-button") { }
}

\NeoAlt\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoAlt\NeoGlobal\query_param("page") === \NeoAlt\NeoGlobal\plugin_settings_page_slug())) { return; }
    wp_enqueue_script("plugin-install"); add_thickbox();
    \NeoAlt\NeoGlobal\enqueue_js("neo-manager-wporg.js");
});
