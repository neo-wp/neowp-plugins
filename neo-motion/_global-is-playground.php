<?php
namespace NeoMotion\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function is_playground() {
    return str_contains(\NeoMotion\NeoGlobal\site_host(), "playground.wordpress.net");
}
