<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Convenience wrapper function around wp_remote_request() to avoid using blank curl requests.
function curl_request($url, $method = "GET", $data = null, $timeout = 30, $headers = [], $suppress_error = false, $follow_redirects = false) {
    if (!is_string($url) || $url === "") { \NeoRename\NeoGlobal\throw_global_exception("Invalid URL: " . $url); }
    if (isset($headers[0])) { \NeoRename\NeoGlobal\throw_global_exception("Headers must be an associative array, not a list"); }

    $args = ["method"  => $method, "headers" => [], "timeout" => $timeout, "redirection" => $follow_redirects ? 10 : 0];
    if ($timeout !== null && $timeout < 1.0) { $args["connect_timeout"] = $timeout; }

    if ($method === "GET") {
        if ($data !== null) { $url = \NeoRename\NeoGlobal\add_or_update_query_params($url, $data); }
    } else if (in_array($method, ["POST", "PATCH", "PUT", "DELETE"])) {
        if ($data !== null) {
            $args["body"] = \NeoRename\NeoGlobal\json_encode_better($data);
            $headers["Content-Type"] = "application/json"; $headers["Content-Length"] = mb_strlen($args["body"], "8bit");
        }
    } else { \NeoRename\NeoGlobal\throw_global_exception("Invalid method: " . $method); }

    if (!isset($headers["User-Agent"])) { $headers["User-Agent"] = "neoCurl " . \NeoRename\NeoGlobal\get_bundle_slug(\NeoRename\NeoGlobal\plugin_slug(), \NeoRename\NeoGlobal\plugin_edition()) . " " . \NeoRename\NeoGlobal\plugin_version(); }
    $args["headers"] = $headers;

    try {
        $response = wp_remote_request($url, $args);
    } catch (\Throwable $e) {
        if ($suppress_error) { return false; }
        \NeoRename\NeoGlobal\throw_global_exception("Fetch error for URL $url: " . $e->getMessage());
    }

    if (is_wp_error($response)) {
        if ($suppress_error) { return false; }
        \NeoRename\NeoGlobal\throw_global_exception("Fetch error for URL $url: " . $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($status_code === "" || $status_code === false || $status_code === null) {
        if ($suppress_error) { return false; }
        \NeoRename\NeoGlobal\throw_global_exception("Fetch error for URL $url: Unable to retrieve HTTP status code (probably timeout)");
    }
    if (!($status_code >= 200 && $status_code <= 299)) { if ($suppress_error) { return false; } \NeoRename\NeoGlobal\throw_curl_exception($url, $status_code, $response_body); }
    return $response_body;
}
