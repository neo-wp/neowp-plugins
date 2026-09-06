<?php
namespace NeoReplace\NeoFreemius; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () {
    if (\NeoReplace\NeoGlobal\is_playground()) { return; }// It does not make sense to enter a license key in the WP playground, so we omit it for our sandbox.
    $settings_render_callback = function () {
        ?>
        <neo-setting-neo-replace id="neo-freemius--settings"><?php
            $params = ["id" => freemius_product_id()];
            if (init_freemius_once_and_get_instance()->get_user()) {
                echo init_freemius_once_and_get_instance()->apply_filters("templates/account.php", fs_get_template("account.php", $params)); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ // Not escaped because template code from the Freemius SDK is already properly escaped
            } else if (!init_freemius_once_and_get_instance()->is_anonymous()) {
                echo init_freemius_once_and_get_instance()->apply_filters("templates/connect.php", fs_get_template("connect.php", $params)); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ // Not escaped because template code from the Freemius SDK is already properly escaped
            } else {
                ?><?php
            }
        ?></neo-setting-neo-replace>
        <neo-setting-neo-replace id="neo-freemius--notices"></neo-setting-neo-replace>
        <neo-setting-neo-replace>
            <div slot="left">
                <h3><?php \NeoReplace\NeoGlobal\echo_neo__("Remove license", "Lizenz entfernen"); ?></h3>
                <p><?php \NeoReplace\NeoGlobal\echo_neo__("Remove all licenses from the website and reset license settings.", "Alle Lizenzen von der Webseite entfernen und Lizenzeinstellungen zurücksetzen."); ?></p>
            </div>
            <div slot="right">
                <neo-button-neo-replace id="neo-freemius--remove-license-button"><?php \NeoReplace\NeoGlobal\echo_neo__("Reset license data", "Lizenzdaten zurücksetzen"); ?></neo-button-neo-replace>
            </div>
        </neo-setting-neo-replace>
    <?php };
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_add_neo_setting_20260326')("neo-freemius", $settings_render_callback);

    $freemius_account_page_load_callback = function () {
        init_freemius_once_and_get_instance()->_account_page_load();
        \NeoReplace\NeoGlobal\enqueue_css("neo-freemius--account-page.css");
        \NeoReplace\NeoGlobal\enqueue_js("neo-freemius--reset-license-data.js");
    };
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($freemius_account_page_load_callback);
    \NeoReplace\NeoGlobal\add_action_hook("admin_footer", function () { ?><?php \NeoReplace\NeoGlobal\backend_page_style_tag_start([]); ?>.wp-submenu>li:has(a[href*="?page=<?php echo esc_attr(\NeoReplace\NeoGlobal\plugin_settings_page_slug()) ?>-account"]) { display: none; }<?php \NeoReplace\NeoGlobal\backend_page_style_tag_end(); ?><?php });
});

\NeoReplace\NeoGlobal\add_action_hook("admin_footer", function () {
    if (\NeoReplace\NeoGlobal\query_param("fs_action") === null) { return; }
    ?><?php \NeoReplace\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
        import { observeOnce } from "<?php echo esc_url(\NeoReplace\NeoGlobal\plugin_url()) ?>/_global--observer.js";
        observeOnce("#neo-freemius--settings", element => setTimeout(() => element.scrollIntoView({ behavior: "smooth" }), 1000));
    <?php \NeoReplace\NeoGlobal\backend_page_script_tag_end(); ?><?php
});

\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    if (!(\NeoReplace\NeoGlobal\query_param("page") === \NeoReplace\NeoGlobal\plugin_settings_page_slug())) { return; }
});

\NeoReplace\NeoGlobal\add_action_hook("admin_head", function () {
    if (!(\NeoReplace\NeoGlobal\query_param("page") === \NeoReplace\NeoGlobal\plugin_settings_page_slug())) { return; }

    ?><?php \NeoReplace\NeoGlobal\backend_page_style_tag_start(["id" => "neo-freemius--hide-fs-notice-inline-css"]); ?>.fs-notice:not(#more-specificity) { display: none !important; }<?php \NeoReplace\NeoGlobal\backend_page_style_tag_end(); ?><?php
    \NeoReplace\NeoGlobal\enqueue_js("neo-freemius--move-notices-on-settings-page.js");
});
