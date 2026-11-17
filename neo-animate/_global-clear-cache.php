<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_all_bundles_clear_cache_20260202($force = false) {
    \NeoAnimate\NeoGlobal\clear_plugin_cache(delete_all: false, force: $force);
}

function interface_all_bundles_clear_license_cache_20260131() {
    \NeoAnimate\NeoGlobal\delete_all(\NeoAnimate\NeoGlobal\cache_path("neo-pro-check"));
}
