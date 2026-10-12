<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDuplicate\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    [$pricing_url, $interface_ok] = \NeoDuplicate\NeoGlobal\call_interface_func_implemented('\NeoDuplicate\NeoFreemius\interface_freemius_pricing_page_url_20260131')();
    $pricing_url ??= "https://" . \NeoDuplicate\NeoGlobal\option__neo_wp_com() . "/pricing/?ref=wp-backend";
    \NeoDuplicate\NeoGlobal\enqueue_js_variable_backend("neoPricingUrl", $pricing_url);
});
