<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function array_filter_better(array $array, $callback = null): array { return array_values(array_filter($array, $callback)); }

function array_unique_better(array $array): array { return array_values(array_unique($array, SORT_REGULAR)); }

function array_diff_better(array $array1, array $array2): array { return array_values(array_diff($array1, $array2)); }

function array_intersect_better(array $array1, array $array2): array { return array_values(array_intersect($array1, $array2)); }
