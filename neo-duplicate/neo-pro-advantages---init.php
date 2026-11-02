<?php
namespace NeoDuplicate\NeoProAdvantages; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDuplicate\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {
        if (!(\NeoDuplicate\NeoGlobal\plugin_slug() === "neo-prod" || \NeoDuplicate\NeoGlobal\plugin_edition() === "dev")) {  }
        ?><neo-setting-neo-duplicate style="position: relative;"> <?php
            [$pricing_url, $interface_ok] = \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoFreemius\interface_freemius_pricing_page_url_20260131')();
            $pricing_url ??= "https://" . \NeoDuplicate\NeoGlobal\option__neo_wp_com() . \NeoDuplicate\NeoGlobal\neo__("", "/de") . "/pricing/?ref=wp-backend-pro-advantages";
            if (\NeoDuplicate\NeoGlobal\plugin_edition() === "wporg") { $pricing_url = "https://" . \NeoDuplicate\NeoGlobal\option__neo_wp_com() . \NeoDuplicate\NeoGlobal\neo__("", "/de") . "/?ref=wp-backend-pro-advantages-wporg"; }
            ?><a href="<?php echo esc_url($pricing_url) ?>" target="_blank" rel="noopener noreferrer">
                <img id="neo-pro-advantages--image" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="neoWP Pro" style="display: block; width: 100%; height: auto; border-radius: 20px;">
            </a>
        </neo-setting-neo-duplicate><?php
    };
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoSettings\interface_add_neo_setting_20260326')("neo-pro-advantages", $settings_render_callback);

    $assets_callback = function () { \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoProAdvantagesImageUrl", \NeoDuplicate\NeoGlobal\get_neo_pro_advantages_url(\NeoDuplicate\NeoGlobal\plugin_edition())); \NeoDuplicate\NeoGlobal\enqueue_js("neo-pro-advantages.js"); };
    \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($assets_callback);
});
