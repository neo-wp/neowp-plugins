<?php
namespace NeoLibrary\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function is_playground() {
    return str_contains(\NeoLibrary\NeoGlobal\site_host(), "playground.wordpress.net");
}
