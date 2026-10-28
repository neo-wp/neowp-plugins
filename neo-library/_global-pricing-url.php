<?php
namespace NeoLibrary\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoLibrary\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    [$pricing_url, $interface_ok] = \NeoLibrary\NeoGlobal\call_interface_func_implemented('\NeoLibrary\NeoFreemius\interface_freemius_pricing_page_url_20260131')();
    $pricing_url ??= "https://" . \NeoLibrary\NeoGlobal\option__neo_wp_com() . "/pricing/?ref=wp-backend";
    \NeoLibrary\NeoGlobal\enqueue_js_variable_backend("neoPricingUrl", $pricing_url);
});
