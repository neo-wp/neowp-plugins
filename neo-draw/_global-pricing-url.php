<?php
namespace NeoDraw\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    [$pricing_url, $interface_ok] = \NeoDraw\NeoGlobal\call_interface_func_implemented('\NeoDraw\NeoFreemius\interface_freemius_pricing_page_url_20260131')();
    $pricing_url ??= "https://" . \NeoDraw\NeoGlobal\option__neo_wp_com() . "/pricing/?ref=wp-backend";
    \NeoDraw\NeoGlobal\enqueue_js_variable_backend("neoPricingUrl", $pricing_url);
});
