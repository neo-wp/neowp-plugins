<?php
namespace NeoOptimize\NeoFreemius; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_freemius_pricing_page_slug_20250613()    { return \NeoOptimize\NeoGlobal\plugin_settings_page_slug() . "-pricing"; }
function interface_freemius_pricing_page_url_20260131()     { return menu_page_url(interface_freemius_pricing_page_slug_20250613(), display: false) ?: null; }
function interface_freemius_settings_section_url_20251005() { [$neo_pro_settings_section_url, $interface_ok] = \NeoOptimize\NeoGlobal\call_interface_func_implemented('\NeoOptimize\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-pro"); return $interface_ok ? $neo_pro_settings_section_url : null; }

\NeoOptimize\NeoGlobal\add_action_hook("admin_footer", function () {
    if (!(\NeoOptimize\NeoGlobal\query_param("page") === interface_freemius_pricing_page_slug_20250613())) { return; }
    ?><?php \NeoOptimize\NeoGlobal\backend_page_style_tag_start([]); ?>.fs-testimonial>:is(header, section) { height: auto !important; }<?php \NeoOptimize\NeoGlobal\backend_page_style_tag_end(); ?><?php 
});
