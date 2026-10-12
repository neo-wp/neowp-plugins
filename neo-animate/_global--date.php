<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function wp_date_timezone_string() { return wp_timezone()->getName(); }

function wp_date_string($format = "Y-m-d H:i:s", $timestamp = null) {
    if (!is_string($format)) { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid format (not a string)"); }
    if (is_float($timestamp)) { $timestamp = intval($timestamp); }
    if ($timestamp !== null && !is_int($timestamp)) { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid timestamp: " . $timestamp); }
    $date_time = new \DateTime("@" . ($timestamp ?? time()));
    $date_time->setTimezone(wp_timezone());
    return $date_time->format($format);
}

function utc_date_string($format = "Y-m-d H:i:s", $timestamp = null) {
    if (!is_string($format)) { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid format (not a string): " . $format); }
    if (is_float($timestamp)) { $timestamp = intval($timestamp); }
    if ($timestamp !== null && !is_int($timestamp)) { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid timestamp: " . $timestamp); }
    return gmdate($format, $timestamp ?? time());
}

function timestamp_from_wp_date_string($date_string, $format = "Y-m-d H:i:s") {
    $date_time = \DateTime::createFromFormat($format, $date_string, wp_timezone());
    if ($date_time === false) { \NeoAnimate\NeoGlobal\throw_global_exception("Could not convert date string to timestamp. Date string: " . $date_string . "."); }
    return $date_time->getTimestamp();
}

function timestamp_from_utc_date_string($date_string, $format = "Y-m-d H:i:s") {
    if (!is_string($date_string)) { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid date string (not a string): " . \NeoAnimate\NeoGlobal\json_encode_better($date_string)); }
    if (!is_string($format))      { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid format (not a string): " .      \NeoAnimate\NeoGlobal\json_encode_better($format)); }
    $date_time = \DateTime::createFromFormat($format, $date_string, new \DateTimeZone("UTC"));
    if ($date_time === false) { \NeoAnimate\NeoGlobal\throw_global_exception("Could not convert date string to timestamp. Date string: " . $date_string . "."); }
    return $date_time->getTimestamp();
}

function unique_planck_date() {
    $micros = function () { [$micro, $unix_sec] = explode(" ", microtime(as_float: false)); return $unix_sec . str_pad(substr($micro, 2, 6), 6, "0", STR_PAD_RIGHT); };
    $microtime_last = $micros();
    $microtime_next = $micros();
    while ($microtime_last === $microtime_next) { $microtime_next = $micros(); }
    $unix_sec = substr($microtime_next, 0, -6);
    $micro    = substr($microtime_next, -6);
    $date_str = gmdate("YmdHis", $unix_sec) . $micro;
    $date_str .= str_pad(wp_rand(0, 999999), 6, "0", STR_PAD_LEFT) . str_pad(wp_rand(0, 999999), 6, "0", STR_PAD_LEFT);
    return $date_str;
}
