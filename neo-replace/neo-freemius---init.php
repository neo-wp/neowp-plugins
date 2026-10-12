<?php
namespace NeoReplace\NeoFreemius; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function freemius_product_id() {
    $hardcoded_value = "12083";
    if (isset(\NeoReplace\NeoGlobal\val("custom")["license_settings"]) && (\NeoReplace\NeoGlobal\val("custom")["license_settings"]["freemius_product_id"] ?? null) !== $hardcoded_value) { \NeoReplace\NeoGlobal\admin_error("Freemius product ID mismatch: " . (\NeoReplace\NeoGlobal\val("custom")["license_settings"]["freemius_product_id"] ?? "[MISSING]") . " != " . $hardcoded_value . ". Update hardcoded value in " . basename(__FILE__) . "."); }
    return $hardcoded_value;
}
function freemius_product_public_key() {
    $hardcoded_value = "pk_01093f748756a4db76d7003ade4ab";
    if (isset(\NeoReplace\NeoGlobal\val("custom")["license_settings"]) && (\NeoReplace\NeoGlobal\val("custom")["license_settings"]["freemius_product_public_key"] ?? null) !== $hardcoded_value) { \NeoReplace\NeoGlobal\admin_error("Freemius public key mismatch: " . (\NeoReplace\NeoGlobal\val("custom")["license_settings"]["freemius_product_public_key"] ?? "[MISSING]") . " != " . $hardcoded_value . ". Update hardcoded value in " . basename(__FILE__) . "."); }
    return $hardcoded_value;
}
function freemius_slug_free() {
    $hardcoded_value = "neo-media";
    if (isset(\NeoReplace\NeoGlobal\val("custom")["license_settings"]) && (\NeoReplace\NeoGlobal\val("custom")["license_settings"]["freemius_slug_free"] ?? null) !== $hardcoded_value) { \NeoReplace\NeoGlobal\admin_error("Freemius free slug mismatch: " . (\NeoReplace\NeoGlobal\val("custom")["license_settings"]["freemius_slug_free"] ?? "[MISSING]") . " != " . $hardcoded_value . ". Update hardcoded value in " . basename(__FILE__) . "."); }
    return $hardcoded_value;
}
function freemius_slug_pro()  {
    $hardcoded_value = "neo-media-pro";
    if (isset(\NeoReplace\NeoGlobal\val("custom")["license_settings"]) && (\NeoReplace\NeoGlobal\val("custom")["license_settings"]["freemius_slug_pro"] ?? null) !== $hardcoded_value) { \NeoReplace\NeoGlobal\admin_error("Freemius pro slug mismatch: " . (\NeoReplace\NeoGlobal\val("custom")["license_settings"]["freemius_slug_pro"] ?? "[MISSING]") . " != " . $hardcoded_value . ". Update hardcoded value in " . basename(__FILE__) . "."); }
    return $hardcoded_value;
}

function init_freemius_once_and_get_instance() {
    static $neo_fs = null; if ($neo_fs === null) {
        if (get_option("fs_accounts")) {
            $fs_accounts = get_option("fs_accounts");
            $freemius_main_file = plugin_basename(\NeoReplace\NeoGlobal\plugin_entry_file_path()); foreach (\NeoReplace\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) { if ($active_plugin["slug"] === "neo-media") { $freemius_main_file = plugin_basename($active_plugin["plugin_entry_file_path"]); break; } }
            $freemius_expected_account_map = ["slug" => freemius_slug_free(), "type" => "plugin", "path" => $freemius_main_file];
            $freemius_current_account_map = $fs_accounts["id_slug_type_path_map"][freemius_product_id()] ?? [];
            $fs_accounts_changed = false;
            if (($freemius_current_account_map["slug"] ?? null) !== $freemius_expected_account_map["slug"] || ($freemius_current_account_map["type"] ?? null) !== $freemius_expected_account_map["type"] || ($freemius_current_account_map["path"] ?? null) !== $freemius_expected_account_map["path"]) {
                $fs_accounts["id_slug_type_path_map"][freemius_product_id()] = $freemius_expected_account_map; $fs_accounts_changed = true;
            }

            if (isset($fs_accounts["sites"][""]) && is_object($fs_accounts["sites"][""]) && strval($fs_accounts["sites"][""]->plugin_id ?? "") === freemius_product_id()) {
                if (!isset($fs_accounts["sites"][freemius_slug_free()]) || !is_object($fs_accounts["sites"][freemius_slug_free()]) || (empty($fs_accounts["sites"][freemius_slug_free()]->license_id) && !empty($fs_accounts["sites"][""]->license_id)) || (($fs_accounts["sites"][""]->updated ?? "") > ($fs_accounts["sites"][freemius_slug_free()]->updated ?? ""))) { $fs_accounts["sites"][freemius_slug_free()] = $fs_accounts["sites"][""]; } unset($fs_accounts["sites"][""]); $fs_accounts_changed = true;
            }
            if (isset($fs_accounts["plans"][""])) {
                if (empty($fs_accounts["plans"][freemius_slug_free()]) && !empty($fs_accounts["plans"][""])) { $fs_accounts["plans"][freemius_slug_free()] = $fs_accounts["plans"][""]; } unset($fs_accounts["plans"][""]); $fs_accounts_changed = true;
            }
            if (isset($fs_accounts["plugin_data"][""])) {
                if (empty($fs_accounts["plugin_data"][freemius_slug_free()]) && !empty($fs_accounts["plugin_data"][""])) { $fs_accounts["plugin_data"][freemius_slug_free()] = $fs_accounts["plugin_data"][""]; } unset($fs_accounts["plugin_data"][""]); $fs_accounts_changed = true;
            }

            if ($fs_accounts_changed) { update_option("fs_accounts", $fs_accounts); } /* Update Freemius option to correctly map this plugin because we use one Freemius account for multiple plugins #suppressLinterWPorgAutoOptionPrefixCheck */
        }

        [$freemius_secret_key, $interface_ok] = \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoDebug\interface_set_up_debug_mode_and_get_freemius_debug_secret_key_20260205')(freemius_slug_free(), freemius_slug_pro()); if (!$interface_ok) { $freemius_secret_key = null; }

        (fn () => require_once(\NeoReplace\NeoGlobal\plugin_path() . "/" . "neo-freemius--thirdparty/start.php"))();
        $neo_fs = fs_dynamic_init([
            "id"                  => freemius_product_id(),
            "slug"                => freemius_slug_free(),
            "premium_slug"        => freemius_slug_pro(),
            "type"                => "plugin",
            "public_key"          => freemius_product_public_key(),
            "premium_suffix"      => "Pro",
            "is_org_compliant" => true, "has_premium_version" => false,
            "has_addons"          => false,
            "has_paid_plans"      => true,
            "is_premium"          => false,
            "is_premium_only"     => \NeoReplace\NeoGlobal\plugin_slug() === "neo-media" && wp_strip_all_tags(wp_unslash($_GET["require_license"] ?? "")) !== "false", /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ /* There is no nonce to verify */
            "trial" => [
                "days"               => 14,
                "is_require_payment" => true,
            ],
            "menu" => [
                "slug"    => \NeoReplace\NeoGlobal\plugin_settings_page_slug(),
                "contact" => false,
                "support" => false,
            ],
            "has_affiliation" => false,

            "secret_key" => $freemius_secret_key,

            "is_live" => $freemius_secret_key !== null ? null : true,
        ]);
        $neo_fs->add_filter("plugin_icon", fn () => \NeoReplace\NeoGlobal\plugin_path_no_symlink_follow() . "/img/_global--logo-icon.svg");
        $neo_fs->add_filter("pricing/discounts_model",  function ($model) { return "relative"; });
        $neo_fs->add_filter("redirect_on_activation",   function () { return false; });
        $neo_fs->add_filter("deactivate_on_activation", function () { return false; });
        \NeoReplace\NeoGlobal\add_filter_hook($neo_fs->get_action_tag("is_submenu_visible"), function ($is_visible, $submenu_id) {  return $is_visible; });

        foreach (\NeoReplace\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
            if (plugin_basename($active_plugin["plugin_entry_file_path"]) === (isset($freemius_main_file) ? $freemius_main_file : null)) { continue; }
            \NeoReplace\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]), function ($actions, $plugin_file) use ($neo_fs) { return $neo_fs->_modify_plugin_action_links_hook($actions, $plugin_file); });
        }
    }
    return $neo_fs;
}
\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () { init_freemius_once_and_get_instance(); });
\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () {
    if (!(\NeoReplace\NeoGlobal\query_param("neo-freemius--suppress-opt-in") === "true")) { return; }
    remove_action("admin_menu", [init_freemius_once_and_get_instance(), "_prepare_admin_menu"], WP_FS__LOWEST_PRIORITY); remove_action("network_admin_menu", [init_freemius_once_and_get_instance(), "_prepare_admin_menu"], WP_FS__LOWEST_PRIORITY);
});

function interface_freemius_get_site_id_20260211()                                     { return init_freemius_once_and_get_instance()->get_site()?->id ?? null; }
function interface_freemius_get_anonymous_id_20260211()                                { return init_freemius_once_and_get_instance()->get_anonymous_id(); }
function interface_freemius_can_use_premium_code__premium_only_20260211()              { return init_freemius_once_and_get_instance()->can_use_premium_code__premium_only(); }
function interface_freemius_add_action_connect_after_license_input_20260211($callback) { init_freemius_once_and_get_instance()->add_action("connect/after_license_input", $callback); }
function interface_freemius_delete_account_event_20260211()                            { init_freemius_once_and_get_instance()->delete_account_event(); }
function interface_freemius_get_eula_url_20260211()                                    { return init_freemius_once_and_get_instance()->get_eula_url(); }
function interface_freemius_get_license_activation_terms_url_20260211()                { return init_freemius_once_and_get_instance()->get_license_activation_terms_url(); }
function interface_freemius_get_usage_tracking_terms_url_20260211()                    { return init_freemius_once_and_get_instance()->get_usage_tracking_terms_url(); }
function interface_freemius_get_pricing_json_20260211() {
    return init_freemius_once_and_get_instance()->get_api_plugin_scope()->get("pricing.json?" . http_build_query([
        "is_enriched" => true, "trial" => false, "sandbox" => false, "s_ctx_type" => false, "s_ctx_id" => false, "s_ctx_ts" => false, "s_ctx_secure" => false,
        "plugin_id" => init_freemius_once_and_get_instance()->get_id(), "plugin_public_key" => init_freemius_once_and_get_instance()->get_public_key(),
    ]), flush: true);
}

function interface_freemius_activate_hook_20260210  ($plugin_slug, $plugin_edition, $plugin_version) { static $first_run = true; if (!$first_run) { return; } $first_run = false; \NeoReplace\NeoGlobal\global_log_with_module_name("neo-freemius", "Running freemius activate hook");   init_freemius_once_and_get_instance()->remove_sticky("trial_promotion"); init_freemius_once_and_get_instance()->_activate_plugin_event_hook(); }
function interface_freemius_deactivate_hook_20260210($plugin_slug, $plugin_edition, $plugin_version) { static $first_run = true; if (!$first_run) { return; } $first_run = false; \NeoReplace\NeoGlobal\global_log_with_module_name("neo-freemius", "Running freemius deactivate hook"); init_freemius_once_and_get_instance()->remove_sticky("trial_promotion"); init_freemius_once_and_get_instance()->_deactivate_plugin_hook(); }
function interface_freemius_uninstall_hook_20260210 ($plugin_slug, $plugin_edition, $plugin_version) { static $first_run = true; if (!$first_run) { return; } $first_run = false; \NeoReplace\NeoGlobal\global_log_with_module_name("neo-freemius", "Running freemius uninstall hook");  init_freemius_once_and_get_instance()->_uninstall_plugin_hook(); }

function freemius_license() {
    static $cache = null; return $cache ??= (function () {
        $license_id = init_freemius_once_and_get_instance()?->get_account_option("sites")[freemius_slug_free()]->license_id ?? null;
        $all_licenses = init_freemius_once_and_get_instance()?->get_account_option("all_licenses")[freemius_product_id()] ?? [];
        $license = null; foreach ($all_licenses as $l) { if ($l->id === $license_id) { $license = $l; break; } }
        return $license;
    })();
}

function interface_freemius_license_id_20260211()         { return freemius_license()?->id         ?? null; }
function interface_freemius_license_secret_key_20260211() { return freemius_license()?->secret_key ?? null; }

function remove_all_license_data() {
    require_once(ABSPATH . "wp-admin/includes/plugin.php"); /* This cleanup requires get_plugins(), which is defined in this WordPress core file. #suppressLinterWporgDirectoryConstantCheck #suppressLinterWPorgAutoCoreIncludeCheck */
    foreach (array_keys(get_plugins()) as $plugin_file) {
        if (dirname(\NeoReplace\NeoGlobal\wp_plugin_dir() . "/" . $plugin_file) === \NeoReplace\NeoGlobal\plugin_path_no_symlink_follow()) { continue; }
        if (!str_starts_with($plugin_file, "neo-")) { continue; }
        return;
    }

    init_freemius_once_and_get_instance()->delete_account_event();

    global $wpdb;
    $option_names = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", "fs_" . $wpdb->esc_like(freemius_slug_free()) . "%", "fs_" . $wpdb->esc_like(freemius_slug_pro()) . "%", "_site_transient_fs_%")); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */ /* Direct database call to clean up not only autoloaded WP options and prevent leaving behind any lingering database junk. */
    foreach ($option_names as $option_name) { delete_option($option_name); }

    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoGlobal\interface_all_bundles_clear_license_cache_20260131', throw_if_interface_not_ok: true)();
}
\NeoReplace\NeoGlobal\register_rest_endpoint("/wp-json/neo/remove-freemius-license-data", "POST", fn () => \NeoReplace\NeoGlobal\current_user_can__neo_freemius__delete_license_data(), function ($get_param) {
    remove_all_license_data();
    return "OKAY";
});

\NeoReplace\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoReset\interface_register_neo_reset_action_20260410')(
        id: "neo-freemius--delete-license",  button_text: \NeoReplace\NeoGlobal\neo__("Delete all license data", "Alle Lizenzdaten löschen"), confirm_title: \NeoReplace\NeoGlobal\neo__("Reset license?", "Lizenz zurücksetzen?"), confirm_text: \NeoReplace\NeoGlobal\neo__("Are you sure you want to remove all license data? You will need to enter the license key again.", "Möchtest du wirklich alle Lizenzdaten entfernen? Der Lizenzschlüssel muss erneut eingegeben werden."),
        action_callback: fn () => remove_all_license_data()
    );
});

(function () {
    foreach (\NeoReplace\NeoEntrypoint\get_neo_active_plugins() as $active_plugin) {
        \NeoReplace\NeoGlobal\add_filter_hook("plugin_action_links_" . plugin_basename($active_plugin["plugin_entry_file_path"]) . ":11", function ($actions, $plugin_file, $plugin_data, $context) {
            if (!str_starts_with($plugin_file, "neo-")) { return $actions; }

            $premium_url = interface_freemius_pricing_page_url_20260131() ?? ("https://" . \NeoReplace\NeoGlobal\option__neo_wp_com() . "/pricing/?ref=wp-backend-get-premium#pricing-table");
            $premium_url_target = (($premium_url_host = wp_parse_url($premium_url, PHP_URL_HOST)) !== null && ($wp_instance_host = wp_parse_url(home_url("/"), PHP_URL_HOST)) !== null && strtolower($premium_url_host) !== strtolower($wp_instance_host)) ? " target=\"_blank\" rel=\"noopener noreferrer\"" : "";
            $actions["upgrade"] = "<a href=\"" . esc_url($premium_url) . "\"" . $premium_url_target . " style=\"font-weight: bold; color: #a03b1f;\">" . \NeoReplace\NeoGlobal\neo__("Get Premium", "Premium holen") . "</a>";
            $actions = ["upgrade" => $actions["upgrade"]] + $actions;

            return $actions;
        });
    }
})();
