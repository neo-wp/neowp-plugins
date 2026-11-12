<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoReplace\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    [$pricing_url, $interface_ok] = \NeoReplace\NeoGlobal\call_interface_func_implemented('\NeoReplace\NeoFreemius\interface_freemius_pricing_page_url_20260131')();
    $pricing_url ??= "https://" . \NeoReplace\NeoGlobal\option__neo_wp_com() . "/pricing/?ref=wp-backend";
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoPricingUrl", $pricing_url);
});
