<?php
namespace NeoRename\NeoProAdvantages; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {
        if (!(\NeoRename\NeoGlobal\plugin_slug() === "neo-prod" || \NeoRename\NeoGlobal\plugin_edition() === "dev")) {  }
        ?><neo-setting-neo-rename style="position: relative;"> <?php
            [$pricing_url, $interface_ok] = \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoFreemius\interface_freemius_pricing_page_url_20260131')();
            $pricing_url ??= "https://" . \NeoRename\NeoGlobal\option__neo_wp_com() . \NeoRename\NeoGlobal\neo__("", "/de") . "/pricing/?ref=wp-backend-pro-advantages";
            if (\NeoRename\NeoGlobal\plugin_edition() === "wporg") { $pricing_url = "https://" . \NeoRename\NeoGlobal\option__neo_wp_com() . \NeoRename\NeoGlobal\neo__("", "/de") . "/?ref=wp-backend-pro-advantages-wporg"; }
            ?><a href="<?php echo esc_url($pricing_url) ?>" target="_blank" rel="noopener noreferrer">
                <img id="neo-pro-advantages--image" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="neoWP Pro" style="display: block; width: 100%; height: auto; border-radius: 20px;">
            </a>
        </neo-setting-neo-rename><?php
    };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_setting_20260326')("neo-pro-advantages", $settings_render_callback);

    $assets_callback = function () { \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoProAdvantagesImageUrl", \NeoRename\NeoGlobal\get_neo_pro_advantages_url(\NeoRename\NeoGlobal\plugin_edition())); \NeoRename\NeoGlobal\enqueue_js("neo-pro-advantages.js"); };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
