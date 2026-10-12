<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function interface_all_bundles_clear_cache_20260202($force = false) {
    \NeoReplace\NeoGlobal\clear_plugin_cache(delete_all: false, force: $force);
}

function interface_all_bundles_clear_license_cache_20260131() {
    \NeoReplace\NeoGlobal\delete_all(\NeoReplace\NeoGlobal\cache_path("neo-pro-check"));
}
