<?php
namespace NeoDuplicate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function str_replace_start(string $search, string $replace, string $subject): string {
    if (!str_starts_with($subject, $search)) { return $subject; }
    return $replace . substr($subject, strlen($search));
}
