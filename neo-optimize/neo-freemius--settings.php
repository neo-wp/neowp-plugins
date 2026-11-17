<?php
namespace NeoOptimize\NeoFreemius; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoOptimize\NeoGlobal\add_action_hook("neo_init", function () {
    if (\NeoOptimize\NeoGlobal\is_playground()) { return; }// It does not make sense to enter a license key in the WP playground, so we omit it for our sandbox.
    $settings_render_callback = function () {
        ?>
        <neo-setting-neo-optimize id="neo-freemius--settings"><?php
            $params = ["id" => freemius_product_id()];
            if (init_freemius_once_and_get_instance()->get_user()) {
                echo init_freemius_once_and_get_instance()->apply_filters("templates/account.php", fs_get_template("account.php", $params)); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ // Not escaped because template code from the Freemius SDK is already properly escaped
            } else if (!init_freemius_once_and_get_instance()->is_anonymous()) {
                echo init_freemius_once_and_get_instance()->apply_filters("templates/connect.php", fs_get_template("connect.php", $params)); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ // Not escaped because template code from the Freemius SDK is already properly escaped
            } else {
                ?><?php
            }
        ?></neo-setting-neo-optimize>
        <neo-setting-neo-optimize id="neo-freemius--notices"></neo-setting-neo-optimize>
        <neo-setting-neo-optimize>
            <div slot="left">
                <h3><?php \NeoOptimize\NeoGlobal\echo_neo__("Remove license", "Lizenz entfernen"); ?></h3>
                <p><?php \NeoOptimize\NeoGlobal\echo_neo__("Remove all licenses from the website and reset license settings.", "Alle Lizenzen von der Webseite entfernen und Lizenzeinstellungen zurücksetzen."); ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-optimize id="neo-freemius--remove-license-button"><?php \NeoOptimize\NeoGlobal\echo_neo__("Reset license data", "Lizenzdaten zurücksetzen"); ?></neo-button-neo-optimize>
            </div>
        </neo-setting-neo-optimize>
    <?php };
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoSettings\interface_add_neo_setting_20260326')("neo-freemius", $settings_render_callback);

    $freemius_account_page_load_callback = function () {
        init_freemius_once_and_get_instance()->_account_page_load();
        \NeoOptimize\NeoGlobal\enqueue_css("neo-freemius--account-page.css");
        \NeoOptimize\NeoGlobal\enqueue_js("neo-freemius--reset-license-data.js");
    };
    \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($freemius_account_page_load_callback);
    \NeoOptimize\NeoGlobal\add_action_hook("admin_footer", function () { ?><?php \NeoOptimize\NeoGlobal\backend_page_style_tag_start([]); ?>.wp-submenu>li:has(a[href*="?page=<?php echo esc_attr(\NeoOptimize\NeoGlobal\plugin_settings_page_slug()) ?>-account"]) { display: none; }<?php \NeoOptimize\NeoGlobal\backend_page_style_tag_end(); ?><?php });
});

\NeoOptimize\NeoGlobal\add_action_hook("admin_footer", function () {
    if (\NeoOptimize\NeoGlobal\query_param("fs_action") === null) { return; }
    ?><?php \NeoOptimize\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
        import { observeOnce } from "<?php echo esc_url(\NeoOptimize\NeoGlobal\plugin_url()) ?>/_global--observer.js";
        observeOnce("#neo-freemius--settings", element => setTimeout(() => element.scrollIntoView({ behavior: "smooth" }), 1000));
    <?php \NeoOptimize\NeoGlobal\backend_page_script_tag_end(); ?><?php
});

\NeoOptimize\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoOptimize\NeoGlobal\query_param("page") === \NeoOptimize\NeoGlobal\plugin_settings_page_slug())) { return; }
});

\NeoOptimize\NeoGlobal\add_action_hook("admin_head", function () {
    if (!(\NeoOptimize\NeoGlobal\query_param("page") === \NeoOptimize\NeoGlobal\plugin_settings_page_slug())) { return; }

    ?><?php \NeoOptimize\NeoGlobal\backend_page_style_tag_start(["id" => "neo-freemius--hide-fs-notice-inline-css"]); ?>.fs-notice:not(#more-specificity) { display: none !important; }<?php \NeoOptimize\NeoGlobal\backend_page_style_tag_end(); ?><?php
    \NeoOptimize\NeoGlobal\enqueue_js("neo-freemius--move-notices-on-settings-page.js");
});
