<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function preg_match_better(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int {
    $matches = $matches ?? []; $result = preg_match($pattern, $subject, $matches, $flags, $offset);
    if ($result === false) { \NeoAlt\NeoGlobal\throw_global_exception("preg_match failed"); }
    return $result;
}

function preg_match_all_better(string $pattern, string $subject, ?array &$matches = null, int $flags = PREG_PATTERN_ORDER, int $offset = 0): int {
    $matches = $matches ?? []; $result = preg_match_all($pattern, $subject, $matches, $flags, $offset);
    if ($result === false) { \NeoAlt\NeoGlobal\throw_global_exception("preg_match_all failed"); }
    return $result;
}

function preg_replace_better(string|array $pattern, string|array $replacement, string|array $subject, int $limit = -1, ?int &$count = null): string|array {
    $count = $count ?? 0; $result = preg_replace($pattern, $replacement, $subject, $limit, $count);
    if ($result === null) { \NeoAlt\NeoGlobal\throw_global_exception("preg_replace failed"); }
    return $result;
}

function preg_replace_callback_better(string|array $pattern, callable $callback, string|array $subject, int $limit = -1, ?int &$count = null, int $flags = 0): string|array {
    $count = $count ?? 0; $result = preg_replace_callback($pattern, $callback, $subject, $limit, $count, $flags);
    if ($result === null) { \NeoAlt\NeoGlobal\throw_global_exception("preg_replace_callback failed"); }
    return $result;
}

function preg_replace_callback_array_better(array $pattern_callback_map, string|array $subject, int $limit = -1, ?int &$count = null, int $flags = 0): string|array {
    $count = $count ?? 0; $result = preg_replace_callback_array($pattern_callback_map, $subject, $limit, $count, $flags);
    if ($result === null) { \NeoAlt\NeoGlobal\throw_global_exception("preg_replace_callback_array failed"); }
    return $result;
}

function preg_split_better(string $pattern, string $subject, int $limit = -1, int $flags = 0): array {
    $result = preg_split($pattern, $subject, $limit, $flags);
    if ($result === false) { \NeoAlt\NeoGlobal\throw_global_exception("preg_split failed"); }
    return $result;
}

function preg_grep_better(string $pattern, array $input, int $flags = 0): array {
    $result = preg_grep($pattern, $input, $flags);
    if ($result === false) { \NeoAlt\NeoGlobal\throw_global_exception("preg_grep failed"); }
    return $result;
}
