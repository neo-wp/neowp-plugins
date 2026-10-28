<?php
namespace NeoOptimize\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function &val($name, $value = null) {
    static $neo_globals = [];
    if (func_num_args() === 2) { $neo_globals[$name] = $value; }
    if (!array_key_exists($name, $neo_globals)) { $neo_globals[$name] = null; }
    return $neo_globals[$name];
}
