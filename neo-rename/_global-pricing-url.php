<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoRename\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    [$pricing_url, $interface_ok] = \NeoRename\NeoGlobal\call_interface_func_implemented('\NeoRename\NeoFreemius\interface_freemius_pricing_page_url_20260131')();
    $pricing_url ??= "https://" . \NeoRename\NeoGlobal\option__neo_wp_com() . "/pricing/?ref=wp-backend";
    \NeoRename\NeoGlobal\enqueue_js_variable_backend("neoPricingUrl", $pricing_url);
});
