<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function is_playground() {
    return str_contains(\NeoDuplicate\NeoGlobal\site_host(), "playground.wordpress.net");
}
