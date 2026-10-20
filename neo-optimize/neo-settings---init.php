<?php
namespace NeoOptimize\NeoSettings; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoOptimize\NeoGlobal\val("neo_settings__sections", []);
function interface_add_neo_settings_section_20260411($id, $module_id_prefixes, $icon, $icon_animated, $title, $description, $coming_soon_url = null, $section_group = null, $render_callback_setting_slot = null) {
    \NeoOptimize\NeoGlobal\val("neo_settings__sections")[] = ["id" => $id, "module_id_prefixes" => $module_id_prefixes, "icon" => $icon, "icon_animated" => $icon_animated, "title" => $title, "description" => $description, "coming_soon_url" => $coming_soon_url, "section_group" => $section_group, "render_callback_setting_slot" => $render_callback_setting_slot];
}
function interface_get_neo_settings_section_url_20260613($section_id) { return admin_url("admin.php?page=" . \NeoOptimize\NeoGlobal\plugin_settings_page_slug() . "#neo-settings--section-" . $section_id); }

\NeoOptimize\NeoGlobal\val("neo_settings__settings", []);
function interface_add_neo_setting_20260326($module_id, $render_callback, $show_to_editor = false) {
    \NeoOptimize\NeoGlobal\val("neo_settings__settings")[] = ["module_id" => $module_id, "render_callback" => $render_callback, "show_to_editor" => $show_to_editor];
}

\NeoOptimize\NeoGlobal\val("neo_settings__asset_loading_callbacks", []);
function interface_add_neo_settings_asset_loading_callback_20250918($callback) { \NeoOptimize\NeoGlobal\val("neo_settings__asset_loading_callbacks")[] = $callback; }

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {
        ?><neo-setting-neo-optimize>
            <div slot="left">
                <h3><?php \NeoOptimize\NeoGlobal\echo_neo__("Move neoWP from menu to Tools", "neoWP vom Menü in die Werkzeuge verschieben") ?></h3>
                <p id="neo-settings--menu-location-description" data-neo-settings--menu-location-notice="<?php \NeoOptimize\NeoGlobal\echo_neo_attr__("The menu item will be hidden when you leave this page.", "Der Menüpunkt wird beim Verlassen dieser Seite ausgeblendet.") ?>"><?php \NeoOptimize\NeoGlobal\echo_neo__("Move neoWP into the WordPress Tools menu and hide the large sidebar entry.", "NeoWP in das WordPress-Menü Werkzeuge verschieben und den großen Sidebar-Eintrag ausblenden.") ?></p>
            </div>
            <div slot="right">
                <?php \NeoOptimize\NeoGlobal\echo_switch_for_option("neo_settings__hide_top_level_menu_enabled", \NeoOptimize\NeoGlobal\neo__("Tools menu", "Werkzeuge-Menü"), js_function_on_change: "window.neoSettingsMenuLocationNoticeDispatchChange") ?> 
            </div>
        </neo-setting-neo-optimize><?php
    };
    interface_add_neo_setting_20260326("neo-settings--menu-location", $settings_render_callback);
});

\NeoOptimize\NeoGlobal\add_action_hook("admin_menu", function () {
    $menu_page_hook_suffix_id = add_menu_page(
        \NeoOptimize\NeoGlobal\neo__("neoWP - Media plugins", "neoWP - Media plugins"),
        \NeoOptimize\NeoGlobal\neo__("neoWP", "neoWP"),
        \NeoOptimize\NeoGlobal\capability__neo_settings(),
        \NeoOptimize\NeoGlobal\plugin_settings_page_slug(),
        function () {
            \NeoOptimize\NeoGlobal\performance_checkpoint("neo-settings menu page", is_start: true);
            \NeoOptimize\NeoGlobal\disallow_enqueue("Use interface_add_neo_settings_asset_loading_callback_20250918 to enqueue JS/CSS on neo-settings pages.", function () {
                [$plugin_list, $plugin_icon_list] = \NeoOptimize\NeoGlobal\update_and_get_plugin_list_and_icons();

                $not_installed_neoplugins = []; foreach ($plugin_list as $plugin_data) { if (!in_array(\NeoOptimize\NeoGlobal\get_plugin_entry_path_relative($plugin_data["plugin-slug"]), array_keys(get_plugins())) && !\NeoOptimize\NeoGlobal\is_module_available($plugin_data["plugin-slug"]) && !\NeoOptimize\NeoGlobal\is_module_available($plugin_data["plugin-slug"], include_suppressed: true)) { $not_installed_neoplugins[] = $plugin_data["plugin-slug"]; } }
                usort($plugin_list, function ($a, $b) use ($not_installed_neoplugins) {
                    $a_not_installed = in_array($a["plugin-slug"], $not_installed_neoplugins);
                    $b_not_installed = in_array($b["plugin-slug"], $not_installed_neoplugins);
                    if (!$a_not_installed && $b_not_installed) { return -1; }
                    if ($a_not_installed && !$b_not_installed) { return +1; }

                    if (!$a["plugin-is-coming-soon"] &&  $b["plugin-is-coming-soon"]) { return -1; }
                    if ( $a["plugin-is-coming-soon"] && !$b["plugin-is-coming-soon"]) { return +1; }

                    [$user_type, $interface_ok] = \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoDomainSettings\interface_get_user_type_20260412')(); if (!$interface_ok) { $user_type = "user"; }
                    if ($user_type === "dev") {
                        if ($a["plugin-slug"] === "neo-prod") { return -1; }
                        if ($b["plugin-slug"] === "neo-prod") { return +1; }
                    }
                    return 0;
                });

                [$color_theme, $interface_ok] = \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoProColorTheme\interface_get_color_theme_20260106')(); if (!$interface_ok) { $color_theme = "theme-neo"; }
                foreach ($plugin_list as $plugin_data) {
                    interface_add_neo_settings_section_20260411(
                        id: $plugin_data["plugin-slug"],
                        module_id_prefixes: [$plugin_data["plugin-slug"]],
                        icon: $plugin_icon_list[$plugin_data["plugin-slug"]][$color_theme]["icon"],
                        icon_animated: $plugin_icon_list[$plugin_data["plugin-slug"]][$color_theme]["icon-animated"],
                        title: $plugin_data["plugin-name"],
                        description: $plugin_data["plugin-slogan"],
                        coming_soon_url: $plugin_data["plugin-is-coming-soon"] ? $plugin_data["plugin-website-url"] : null,
                        section_group: (in_array($plugin_data["plugin-slug"], $not_installed_neoplugins) && \NeoOptimize\NeoGlobal\plugin_slug() !== "neo-plugins") || (\NeoOptimize\NeoGlobal\plugin_slug() === "neo-plugins" && $plugin_data["plugin-is-coming-soon"]) ? \NeoOptimize\NeoGlobal\neo__("👀 Show all neoPlugins (+" . count($not_installed_neoplugins) . ")", "👀 Alle neoPlugins anzeigen (+" . count($not_installed_neoplugins) . ")") : "",
                        render_callback_setting_slot: function ($setting_slot) use ($plugin_data) {
                            [$_, $interface_ok] = \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoManagerInstaller\interface_neo_manager_render_settings_card_slot_20260327')($plugin_data, $setting_slot);
                            if (!$interface_ok) { \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoManagerWporg\interface_neo_manager_render_settings_card_slot_20260327')($plugin_data, $setting_slot); }
                        }
                    );
                }

                interface_add_neo_settings_section_20260411(
                    id: "neo-pro",
                    module_id_prefixes: ["neo-pro", "neo-freemius"],
                    icon: \NeoOptimize\NeoGlobal\plugin_url() . "/img/plugin-module-neo-pro-icon.svg", icon_animated: null,
                    title: \NeoOptimize\NeoGlobal\neo__("neoPro", "neoPro"),
                    description: \NeoOptimize\NeoGlobal\neo__("Manage your neoPro license and access extended features.", "Verwalte deine neoPro-Lizenz und erhalte Zugriff auf erweiterte Funktionen.")
                );
                interface_add_neo_settings_section_20260411(
                    id: "neo-ai",
                    module_id_prefixes: ["neo-ai"],
                    icon: \NeoOptimize\NeoGlobal\plugin_url() . "/img/plugin-module-neo-ai-icon.svg", icon_animated: null,
                    title: \NeoOptimize\NeoGlobal\neo__("neoAI", "neoAI"),
                    description: \NeoOptimize\NeoGlobal\neo__("Configure AI connections for neoWP features.", "AI-Verbindungen für neoWP-Features konfigurieren.")
                );
                interface_add_neo_settings_section_20260411(
                    id: "neo-support-reset-log",
                    module_id_prefixes: ["neo-support--support", "neo-support--feedback", "neo-support-codes--support-codes", "neo-settings--menu-location", "neo-log", "neo-reset", "neo-support--privacy-policy"],
                    icon: \NeoOptimize\NeoGlobal\plugin_url() . "/img/plugin-module-neo-support-icon.svg", icon_animated: null,
                    title: \NeoOptimize\NeoGlobal\neo__("neoSupport", "neoSupport"),
                    description: \NeoOptimize\NeoGlobal\neo__("Access support resources, reset settings, and view logs for troubleshooting.", "Zugriff auf Support-Ressourcen, Einstellungen zurücksetzen und Logs zur Fehlerbehebung anzeigen.")
                );
                interface_add_neo_settings_section_20260411(
                    id: "neo-mail",
                    module_id_prefixes: ["neo-tool-mail-send"],
                    icon: \NeoOptimize\NeoGlobal\plugin_url() . "/img/plugin-module-neo-mail-icon.svg", icon_animated: null,
                    title: \NeoOptimize\NeoGlobal\neo__("neoMail", "neoMail"),
                    description: \NeoOptimize\NeoGlobal\neo__("File-based email jobs.", "Dateibasierte E-Mail-Jobs.")
                );
                interface_add_neo_settings_section_20260411(
                    id: "neo-website",
                    module_id_prefixes: ["neo-statistics", "neo-website-ai-connection"],
                    icon: \NeoOptimize\NeoGlobal\plugin_url() . "/img/plugin-module-neo-website-icon.svg", icon_animated: null,
                    title: \NeoOptimize\NeoGlobal\neo__("neoWebsite", "neoWebsite"),
                    description: \NeoOptimize\NeoGlobal\neo__("Website tools and statistics.", "Website-Tools und Statistiken.")
                );
                interface_add_neo_settings_section_20260411(
                    id: "neo-developer",
                    module_id_prefixes: ["neo-tool-passkey", "neo-domain-settings", "neo-license-server", "neo-tool-wporg-search-lab", "neo-tool-wporg-installs", "neo-debug", "neo-symlink", "neo-demo-content", "neo-demo-screenshot"],
                    icon: \NeoOptimize\NeoGlobal\plugin_url() . "/img/plugin-module-neo-developer-icon.svg", icon_animated: null,
                    title: \NeoOptimize\NeoGlobal\neo__("neoDeveloper", "neoDeveloper"),
                    description: \NeoOptimize\NeoGlobal\neo__("Developer tools and settings for advanced users.", "Entwicklertools und Einstellungen für fortgeschrittene Nutzer.")
                );

                ?><div class="wrap">
                    <h1 id="neo-settings--top"><?php \NeoOptimize\NeoGlobal\echo_neo__("neoWP - Media Plugins", "neoWP - Media Plugins"); ?></h1>
                    <?php \NeoOptimize\NeoGlobal\backend_page_style_tag_start([]); ?>
                        html { scroll-behavior: smooth; }
                        .wrap { --neo-settings--section-card-width: 800px; --neo-settings--section-space: 30px; --neo-settings--section-outer-width: calc(var(--neo-settings--section-card-width) + (var(--neo-settings--section-space) * 2)); }
                        #wpbody-content { overflow: hidden; }
                        #wpbody-content { padding-bottom: 40px; }
                        #wpbody-content { font-size: 16px; } #wpbody-content p { font-size: 1em; }
                        .neo-settings--sections, .neo-settings--section-group { display: flex; flex-direction: column; gap: 20px; }
                        .neo-settings--section-group-header { display: flex; justify-content: center; width: var(--neo-settings--section-outer-width); max-width: 100%; }
                        .neo-settings--section-group-toggle { display: inline-flex; align-items: center; justify-content: center; gap: 10px; color: #606060; background: transparent; border: none; font-size: 1.5em; cursor: pointer; &:focus-visible { outline: 2px solid blue; } }
                        .neo-settings--section-group-header-chevron { display: inline-flex; align-items: center; justify-content: center; width: 1.5em; height: 1.5em; flex-shrink: 0; transition: transform 200ms ease; svg { width: 24px; } }
                        .neo-settings--section-group-toggle.neo-settings--section-group-toggle-open .neo-settings--section-group-header-chevron { transform: rotate(180deg); }
                        .neo-settings--section-group { width: fit-content; max-width: 100%; opacity: 1; transition: max-height 400ms ease, opacity 200ms linear; }
                        .neo-settings--section-group.neo-settings--section-group-collapsed { transition: max-height 200ms linear, opacity 200ms linear; }
                        .neo-settings--section-group[hidden] { display: none; }
                        .neo-settings--section-group-separator { border: 0; border-top: 1px solid rgba(0, 0, 0, 0.08); width: calc(100% - 40px); }
                        @media (max-width: 767px) { .wrap { --neo-settings--section-space: 20px; } }
                    <?php \NeoOptimize\NeoGlobal\backend_page_style_tag_end(); ?>
                    <?php

                    $sections_with_settings = [];
                    foreach (\NeoOptimize\NeoGlobal\val("neo_settings__sections") as $settings_section) {
                        $settings_for_section = [];
                        foreach ($settings_section["module_id_prefixes"] as $module_id_prefix) {
                            if ($settings_section["id"] === "neo-symlink") { break; }
                            $settings_for_section = array_merge($settings_for_section, \NeoOptimize\NeoGlobal\array_filter_better(\NeoOptimize\NeoGlobal\val("neo_settings__settings"), fn ($setting) => str_starts_with($setting["module_id"], $module_id_prefix)));
                        }
                        if (!\NeoOptimize\NeoGlobal\current_user_can__neo_settings__see_all_settings()) { $settings_for_section = \NeoOptimize\NeoGlobal\array_filter_better($settings_for_section, fn ($setting) => $setting["show_to_editor"]); }

                        if (empty($settings_for_section) && empty($settings_section["render_callback_setting_slot"])) { continue; }
                        if (!\NeoOptimize\NeoGlobal\current_user_can__neo_settings__see_all_settings() && empty($settings_for_section)) { continue; }

                        $settings_section["render_callbacks_settings"] = [];
                        foreach ($settings_for_section as $setting_index => $setting) { $settings_section["render_callbacks_settings"][$setting["module_id"] . "--" . $setting_index] = $setting["render_callback"]; }
                        $sections_with_settings[] = $settings_section;
                        foreach ($settings_for_section as $setting) { \NeoOptimize\NeoGlobal\val("neo_settings__settings", \NeoOptimize\NeoGlobal\array_filter_better(\NeoOptimize\NeoGlobal\val("neo_settings__settings"), fn ($s) => $s["module_id"] !== $setting["module_id"])); }
                    }

                    foreach (\NeoOptimize\NeoGlobal\val("neo_settings__settings") as $setting) {
                        if (!\NeoOptimize\NeoGlobal\current_user_can__neo_settings__see_all_settings() && !$setting["show_to_editor"]) { continue; }
                        $sections_with_settings[] = [
                            "id"                        => $setting["module_id"],
                            "title"                     => lcfirst(str_replace(" ", "", ucwords(str_replace("-", " ", $setting["module_id"])))),
                            "render_callbacks_settings" => [$setting["module_id"] => $setting["render_callback"]],
                        ];
                    }

                    ?><div class="neo-settings--sections" style="margin-top: 20px;"><?php 
                        $current_section_group = null;
                        foreach ($sections_with_settings as $settings_section) {
                            \NeoOptimize\NeoGlobal\performance_checkpoint("neo-settings section " . $settings_section["id"], is_start: true);

                            if (($settings_section["section_group"] ?? null) && $settings_section["section_group"] !== $current_section_group) {
                                if ($current_section_group) { ?></div><?php }
                                ?>
                                <div class="neo-settings--section-group-header">
                                    <button class="neo-settings--section-group-toggle">
                                        <span class="neo-settings--section-group-header-label"><?php echo esc_html($settings_section["section_group"]) ?></span>
                                        <span class="neo-settings--section-group-header-chevron"><svg viewBox="0 0 24 24" fill="none"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>
                                    </button>
                                </div>
                                <div class="neo-settings--section-group neo-settings--section-group-collapsed" hidden><?php
                                $current_section_group = $settings_section["section_group"];
                            } else if (!($settings_section["section_group"] ?? null) && $current_section_group) {
                                ?><hr class="neo-settings--section-group-separator" /></div><?php
                                $current_section_group = null;
                            }

                            $title = \NeoOptimize\NeoGlobal\preg_replace_better('/(?<=[a-z])(?=[A-Z])/u', "\u{00AD}", $settings_section["title"] ?? "");
                            ?><neo-setting-section-neo-optimize id="neo-settings--section-<?php echo esc_attr($settings_section["id"]) ?>" section-title="<?php echo esc_attr($title) ?>" section-description="<?php echo esc_attr($settings_section["description"] ?? "") ?>" icon="<?php echo esc_url($settings_section["icon"] ?? "") ?>" icon-animated="<?php echo ($settings_section["icon_animated"] ?? null) ? esc_url($settings_section["icon_animated"]) : ""; ?>" <?php echo ($settings_section["coming_soon_url"] ?? null) ? 'coming-soon="' . esc_attr($settings_section["coming_soon_url"]) . '"' : ""; ?>>
                                <?php if ($settings_section["render_callback_setting_slot"] ?? null) { ?> 
                                    <?php foreach (["tooltip", "switch", "install-options", "reload-button"] as $part_type) {
                                        ?><div slot="<?php echo esc_attr($part_type) ?>">
                                            <?php try { $settings_section["render_callback_setting_slot"]($part_type); } catch (\Throwable $e) { \NeoOptimize\NeoGlobal\echo_neo__("Error while rendering section header " . $part_type . " callback", "Fehler beim Rendern des Abschnitts-Header-" . $part_type); \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-settings", "Error while rendering section header " . $part_type . " callback: " . get_class($e) . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString()); } ?>
                                        </div><?php
                                    } ?>
                                <?php } ?>
                                <div slot="settings"><?php
                                    foreach ($settings_section["render_callbacks_settings"] ?? [] as $module_id => $setting) {
                                        \NeoOptimize\NeoGlobal\performance_checkpoint("neo-settings section " . $settings_section["id"] . " setting " . $module_id, is_start: true);
                                        try { $setting(); } catch (\Throwable $e) { \NeoOptimize\NeoGlobal\echo_neo__("Error while rendering setting", "Fehler beim Rendern der Einstellung"); \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-settings", "Error while rendering setting: " . get_class($e) . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString()); }
                                        \NeoOptimize\NeoGlobal\performance_checkpoint("neo-settings section " . $settings_section["id"] . " setting " . $module_id, is_end: true);
                                    }
                                ?></div>
                            </neo-setting-section-neo-optimize><?php
                            \NeoOptimize\NeoGlobal\performance_checkpoint("neo-settings section " . $settings_section["id"], is_end: true);
                        }
                        if ($current_section_group) { ?></div><?php }
                    ?></div>

                    <div id="neo-settings--anti-scroll-glitch-spacing-element" style="width: 100%; height: 30vh;"></div>
                </div>
                <div id="neo-settings--footer-thankyou">
                    <?php \NeoOptimize\NeoGlobal\echo_neo__("Thank you for creating with", "Danke für dein Vertrauen in") ?> <a href="<?php echo esc_url(\NeoOptimize\NeoGlobal\get_plugin_download_url()) ?>" target="_blank">neo-wp.com</a>.
                    <?php \NeoOptimize\NeoGlobal\backend_page_script_tag_start(["type" => "speculationrules"]); ?>{ "prefetch": [{ "urls": ["<?php echo esc_url(\NeoOptimize\NeoGlobal\get_plugin_download_url()) ?>"] }] }<?php \NeoOptimize\NeoGlobal\backend_page_script_tag_end(); ?>
                    <?php \NeoOptimize\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
                        import { observeOnce } from "<?php echo esc_url(\NeoOptimize\NeoGlobal\plugin_url()) ?>/_global--observer.js";
                        observeOnce("#footer-thankyou", (node) => { node.prepend(document.querySelector("#neo-settings--footer-thankyou")); });
                    <?php \NeoOptimize\NeoGlobal\backend_page_script_tag_end(); ?>
                </div>

                <?php \NeoOptimize\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
                    import { domLoaded, addEventListenerWithInitialCall } from "<?php echo esc_url(\NeoOptimize\NeoGlobal\plugin_url()) ?>/_global--observer.js";
                    domLoaded(() => {
                        const sections = document.querySelectorAll("neo-setting-section-neo-optimize");
                        addEventListenerWithInitialCall(window, "scroll", () => {
                            const scrollY = window.scrollY;
                            for (const anyLink of document.querySelectorAll("#toplevel_page_<?php echo esc_attr(\NeoOptimize\NeoGlobal\plugin_settings_page_slug()) ?> li")) { anyLink.classList.remove("current"); }

                            if (Math.ceil(document.querySelector("html").scrollTop + document.querySelector("html").clientHeight) >= document.querySelector("html").scrollHeight) {
                                [...document.querySelectorAll(`#toplevel_page_<?php echo esc_attr(\NeoOptimize\NeoGlobal\plugin_settings_page_slug()) ?> a[href*="#"]`)].pop().parentElement.classList.add("current");
                                return;
                            }

                            for (const section of [...sections].reverse()) {
                                const sectionMenuLink = document.querySelector(`#toplevel_page_<?php echo esc_attr(\NeoOptimize\NeoGlobal\plugin_settings_page_slug()) ?> a[href="admin.php?page=<?php echo esc_attr(\NeoOptimize\NeoGlobal\plugin_settings_page_slug()) ?>#${section.getAttribute('id')}"]`);
                                if (!sectionMenuLink) { continue; }
                                if (section.offsetTop < scrollY + window.innerHeight / 2) {
                                    sectionMenuLink.parentElement.classList.add("current");
                                    return;
                                }
                            }
                        });
                    });
                <?php \NeoOptimize\NeoGlobal\backend_page_script_tag_end(); ?>
                <?php
            });
            \NeoOptimize\NeoGlobal\performance_checkpoint("neo-settings menu page", is_end: true);
        },
        icon_url: "data:image/svg+xml;base64," . base64_encode(\NeoOptimize\NeoGlobal\fs_file_get_contents(\NeoOptimize\NeoGlobal\plugin_path() . "/img/neo-settings--icon.svg")),
    );

    \NeoOptimize\NeoGlobal\add_action_hook("load-$menu_page_hook_suffix_id", function () {
        \NeoOptimize\NeoGlobal\enqueue_js("neo-settings.js");
        foreach (\NeoOptimize\NeoGlobal\val("neo_settings__asset_loading_callbacks") as $callback) { try { $callback(); } catch (\Throwable $e) { \NeoOptimize\NeoGlobal\global_warn_with_module_name("neo-settings", "Error while loading menu page assets: " . get_class($e) . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString()); } }
    });

    $submenu_entries = [
        \NeoOptimize\NeoGlobal\neo__("neoPlugins", "neoPlugins") => "#neo-settings--top",
        \NeoOptimize\NeoGlobal\neo__("License", "Lizenz")        => "#neo-settings--section-neo-pro",
        \NeoOptimize\NeoGlobal\neo__("Support", "Support")       => "#neo-settings--section-neo-support-reset-log",
    ];
    foreach ($submenu_entries as $submenu_page_title => $section_id) {
        add_submenu_page(
            \NeoOptimize\NeoGlobal\plugin_settings_page_slug(),
            $submenu_page_title,
            $submenu_page_title,
            \NeoOptimize\NeoGlobal\capability__neo_settings(),
            \NeoOptimize\NeoGlobal\plugin_settings_page_slug() . $section_id,
            function () { },
        );
    }
    if (\NeoOptimize\NeoGlobal\option__neo_settings__hide_top_level_menu_enabled()) {
        $tools_menu_hook_suffix_id = add_submenu_page("tools.php", \NeoOptimize\NeoGlobal\neo__("neoWP", "neoWP"), \NeoOptimize\NeoGlobal\neo__("neoWP", "neoWP"), \NeoOptimize\NeoGlobal\capability__neo_settings(), \NeoOptimize\NeoGlobal\plugin_settings_page_slug(), function () { global $pagenow; if ($pagenow !== "tools.php") { return; } wp_safe_redirect(admin_url("admin.php?page=" . \NeoOptimize\NeoGlobal\plugin_settings_page_slug())); exit; });
        \NeoOptimize\NeoGlobal\add_action_hook("load-$tools_menu_hook_suffix_id", function () { global $pagenow; if ($pagenow !== "tools.php") { return; } wp_safe_redirect(admin_url("admin.php?page=" . \NeoOptimize\NeoGlobal\plugin_settings_page_slug())); exit; });
    }
});

\NeoOptimize\NeoGlobal\add_action_hook("current_screen", function () {
    if (!\NeoOptimize\NeoGlobal\option__neo_settings__hide_top_level_menu_enabled()) { return; }
    \NeoOptimize\NeoGlobal\enqueue_js("neo-settings--sidebar-tutorial.js");
});

\NeoOptimize\NeoGlobal\add_action_hook("admin_footer", function () {
    ?><?php \NeoOptimize\NeoGlobal\backend_page_style_tag_start([]); ?>
        #toplevel_page_<?php echo esc_attr(\NeoOptimize\NeoGlobal\plugin_settings_page_slug()) ?> > .wp-submenu > .wp-first-item { display: none; }
    <?php \NeoOptimize\NeoGlobal\backend_page_style_tag_end(); ?><?php
});

\NeoOptimize\NeoGlobal\add_action_hook("admin_footer", function () {
    if (!\NeoOptimize\NeoGlobal\option__neo_settings__hide_top_level_menu_enabled()) { return; }
    if (\NeoOptimize\NeoGlobal\query_param("page") === \NeoOptimize\NeoGlobal\plugin_settings_page_slug()) { return; }
    ?><?php \NeoOptimize\NeoGlobal\backend_page_style_tag_start([]); ?>
        #toplevel_page_<?php echo esc_attr(\NeoOptimize\NeoGlobal\plugin_settings_page_slug()) ?> { display: none; }
    <?php \NeoOptimize\NeoGlobal\backend_page_style_tag_end(); ?><?php
});

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    foreach (\NeoOptimize\NeoEntrypoint\get_neo_active_plugins() as $neo_plugin) {
        \NeoOptimize\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($neo_plugin["plugin_entry_file_path"] ?? "") . ":17", function ($links) {
            array_unshift($links, '<a href="admin.php?page=' . \NeoOptimize\NeoGlobal\plugin_settings_page_slug() . '">' . \NeoOptimize\NeoGlobal\neo__("neoSettings", "neoSettings") . '</a>');
            return $links;
        });
    }
});
