<?php
namespace NeoRename\NeoReset; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\val("neo_reset__actions", []);
function interface_register_neo_reset_action_20260410($id, $button_text, $confirm_title, $confirm_text, $action_callback) {
    \NeoRename\NeoGlobal\val("neo_reset__actions", array_merge(\NeoRename\NeoGlobal\val("neo_reset__actions"), [[
        "id" => $id,
        "button_text" => $button_text,
        "confirm_title" => $confirm_title,
        "confirm_text" => $confirm_text,
        "action_callback" => $action_callback,
    ]]));
}

\NeoRename\NeoGlobal\add_action_hook("neo_init", function () {
    interface_register_neo_reset_action_20260410(
        id: "neo-reset--delete-localstorage-and-cookies", button_text: \NeoRename\NeoGlobal\neo__("Clear cookies and localStorage", "Cookies und localStorage löschen"), confirm_title: \NeoRename\NeoGlobal\neo__("Delete all neoCookies and localStorage?", "Alle neoCookies und localStorage löschen?"), confirm_text: \NeoRename\NeoGlobal\neo__("Are you sure you want to delete all cookies and localStorage data? For example notice dialogs appear again.", "Möchtest du wirklich alle Cookies und gespeicherten lokalen Daten löschen? Dadurch werden z.B. Hinweisdialoge wieder angezeigt."),
        action_callback: function () { }
    );
    interface_register_neo_reset_action_20260410(
        id: "neo-reset--clear-cache", button_text: \NeoRename\NeoGlobal\neo__("Clear cache", "Cache leeren"), confirm_title: \NeoRename\NeoGlobal\neo__("Clear all caches?", "Alle Caches leeren?"), confirm_text: \NeoRename\NeoGlobal\neo__("Are you sure you want to clear all cache folders and third-party caches?", "Möchtest du wirklich alle Cache-Ordner und Drittanbieter-Caches löschen?"),
        action_callback: function () { \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoGlobal\interface_all_bundles_clear_cache_20260202', throw_if_interface_not_ok: true)(force: true); }
    );
    if (!(\NeoRename\NeoGlobal\option__neo_wp_com() === "neo-wp.com")) {
        interface_register_neo_reset_action_20260410(
            id: "neo-reset--reset-prod-domain", button_text: \NeoRename\NeoGlobal\neo__("Reset neoWP domain to production", "neoWP-Domain auf Prod zurücksetzen"), confirm_title: \NeoRename\NeoGlobal\neo__("Reset neoWP domain?", "neoWP-Domain zurücksetzen?"), confirm_text: \NeoRename\NeoGlobal\neo__("Are you sure you want to reset the neoWP remote domain to the production domain?", "Möchtest du wirklich die neoWP-Remote-Domain auf die Prod-Domain zurücksetzen?"),
            action_callback: function () { \NeoRename\NeoGlobal\option__neo_wp_com("neo-wp.com"); }
        );
    }
    interface_register_neo_reset_action_20260410(
        id: "neo-reset--delete-settings", button_text: \NeoRename\NeoGlobal\neo__("Reset settings", "Einstellungen zurücksetzen"), confirm_title: \NeoRename\NeoGlobal\neo__("Reset all plugin settings?", "Alle Plugin-Einstellungen zurücksetzen?"), confirm_text: \NeoRename\NeoGlobal\neo__("Are you sure you want to delete all plugin settings?", "Möchtest du wirklich alle Plugin-Einstellungen löschen?"),
        action_callback: function () { \NeoRename\NeoGlobal\delete_all_neo_options(); }
    );
    interface_register_neo_reset_action_20260410(
        id: "neo-reset--delete-license-cache", button_text: \NeoRename\NeoGlobal\neo__("Delete license cache", "Lizenz-Cache löschen"), confirm_title: \NeoRename\NeoGlobal\neo__("Delete license cache?", "Lizenz-Cache löschen?"), confirm_text: \NeoRename\NeoGlobal\neo__("Are you sure you want to delete the license check cache? The license itself will remain intact.", "Möchtest du wirklich den Lizenzprüfungs-Cache löschen? Die Lizenz selbst bleibt unberührt."),
        action_callback: function () { \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoGlobal\interface_all_bundles_clear_license_cache_20260131', throw_if_interface_not_ok: true)(); }
    );

    usort(\NeoRename\NeoGlobal\val("neo_reset__actions"), function ($a, $b) {
             if ( str_starts_with($a["id"], "neo-reset--") && !str_starts_with($b["id"], "neo-reset--")) { return -1; }
        else if (!str_starts_with($a["id"], "neo-reset--") &&  str_starts_with($b["id"], "neo-reset--")) { return  1; }
        else { return 0; }
    });

    $settings_render_callback = function () {
        ?><neo-setting-neo-rename>
            <div slot="left">
                <h3><?php \NeoRename\NeoGlobal\echo_neo__("Clear Cache & Reset Settings", "Cache leeren & Einstellungen zurücksetzen") ?></h3>
                <p><?php \NeoRename\NeoGlobal\echo_neo__("Here we go again. Reset the plugin to its original state, as if freshly installed.", "Setze das Plugin in den Ursprungszustand zurück, als wäre es frisch installiert.") ?></p>
            </div>
            <div slot="right">
                <neo-dropdown-button-neo-rename class="neo-reset--dropdown" button-text="<?php \NeoRename\NeoGlobal\echo_neo_attr__("OK", "OK") ?>">
                    <option value="" selected disabled><?php \NeoRename\NeoGlobal\echo_neo__("Select action", "Aktion auswählen") ?></option>
                    <?php foreach (\NeoRename\NeoGlobal\val("neo_reset__actions") as $action) {
                        ?><option value="<?php echo esc_attr($action["id"]) ?>" data-neo-reset--confirm-title="<?php echo esc_attr($action["confirm_title"]) ?>" data-neo-reset--confirm-text="<?php echo esc_attr($action["confirm_text"]) ?>"><?php echo esc_html($action["button_text"]) ?></option><?php 
                    } ?>
                </neo-dropdown-button-neo-rename>
            </div>
            <?php \NeoRename\NeoGlobal\backend_page_style_tag_start([]); ?>.neo-reset--dropdown { max-width: min(320px, calc(100vw - 140px)); }<?php \NeoRename\NeoGlobal\backend_page_style_tag_end(); ?>
        </neo-setting-neo-rename>
    <?php };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_setting_20260326')("neo-reset", $settings_render_callback);

    $enqueue_assets_callback = function () { \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoResetPluginPageUrl", admin_url("plugins.php")); \NeoRename\NeoGlobal\enqueue_js("neo-reset.js"); };
    \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoSettings\interface_add_neo_settings_asset_loading_callback_20250918')($enqueue_assets_callback);
});

\NeoRename\NeoGlobal\register_rest_endpoint("/wp-json/neo/neo-reset", "POST", fn () => \NeoRename\NeoGlobal\current_user_can__neo_reset(), function ($get_param) {
    $reset_action = $get_param("reset-action");
    \NeoRename\NeoGlobal\global_log_with_module_name("neo-reset", "Reset action: " . $reset_action);
    foreach (\NeoRename\NeoGlobal\val("neo_reset__actions") as $action) {
        if (!($action["id"] === $reset_action)) { continue; }
        $action["action_callback"]();
        return "OKAY";
    }
    \NeoRename\NeoGlobal\throw_global_exception("Invalid reset action: " . $reset_action);
});
