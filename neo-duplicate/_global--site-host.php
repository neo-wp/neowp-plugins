<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function site_host() {
    static $cache = null; return $cache ??= (function () {
        if (is_callable([\Freemius::class, "get_unfiltered_site_url"])) { return \Freemius::get_unfiltered_site_url(null, true, false); }

        if (!is_multisite() && defined("WP_SITEURL")) { $site_url = WP_SITEURL; }
        else {
            global $wpdb;
            $site_url = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", "siteurl")); /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
            $site_url = set_url_scheme($site_url);
        }

        if (!str_starts_with($site_url, "http")) { return $site_url; }
        $protocol_position = strpos($site_url, "://");
        if ($protocol_position > 5) { return $site_url; }
        return substr($site_url, $protocol_position + 3);
    })();
}
