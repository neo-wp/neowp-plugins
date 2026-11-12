<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Wrapper around register_rest_route for convenience and extended permission checks.
\NeoAnimate\NeoGlobal\val("neo_global__rest_endpoints", []);
function register_rest_endpoint($path, $method, $permission_callback, $callback) {
    if (!is_callable($permission_callback)) { \NeoAnimate\NeoGlobal\throw_global_exception("The permission callback must be a callable."); }
    if (!str_starts_with($path, "/wp-json/neo/")) { \NeoAnimate\NeoGlobal\throw_global_exception("The endpoint path must start with '/wp-json/neo/'."); }
    \NeoAnimate\NeoGlobal\add_action_hook("rest_api_init", function () use ($path, $method, $permission_callback, $callback) {
        foreach (is_array($method) ? $method : [$method] as $http_method) {
            if (!in_array($http_method, ["GET", "POST"])) { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid method $http_method - it must be 'GET' or 'POST'. Don't use e.g. PUT because the request body isn't passed through e.g. in the WP Playground."); }
            $path_parts = explode("/", $path);
            $namespace = $path_parts[2]; $route = "/" . implode("/", array_slice($path_parts, 3));
            register_rest_route($namespace, $route, [
                "methods" => $http_method,
                "permission_callback" => function ($request) use ($permission_callback, $path, $http_method) {
                    $permission_result = $permission_callback();

                    if ($permission_result === "public") { return true; }

                    $nonce_raw = $request->get_header("X-WP-Nonce") ?: $request->get_param("_wpnonce");
                    $nonce = is_string($nonce_raw) ? sanitize_text_field(wp_unslash($nonce_raw)) : "";
                    if (!wp_verify_nonce($nonce, "wp_rest")) { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global--endpoint", "Endpoint access failed for endpoint $path ($http_method): Invalid nonce"); return new \WP_Error("invalid_nonce", "Invalid nonce", ["status" => 401]); }

                    if (!is_user_logged_in()) { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global--endpoint", "Endpoint access failed for endpoint $path ($http_method): Not logged in"); return new \WP_Error("not_logged_in", "You are not currently logged in.", ["status" => 401]); }
                    if (!$permission_result)  { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global--endpoint", "Endpoint access failed for endpoint $path ($http_method): No permission"); return new \WP_Error("permission_denied", "Your user role does not have permission to access this endpoint.", ["status" => 403]); }
                    return true;
                },
                "callback" => function ($request) use ($callback, $path, $http_method) {
                    \NeoAnimate\NeoGlobal\performance_checkpoint("endpoint $http_method $path", is_start: true);
                    $get_param = function ($key) use ($request) {
                        if (sanitize_key($key) !== $key) { \NeoAnimate\NeoGlobal\throw_global_exception("WP only allows alphanumeric characters and underscores for the parameter name!"); }
                        return $request->get_param($key) ?? ($request->get_json_params() ?: [])[$key] ?? ($request->get_file_params() ?: [])[$key] ?? null;
                    };
                    try {
                        if (count((new \ReflectionFunction($callback))->getParameters()) === 1) { $response = $callback($get_param); }
                        else { $response = $callback(); }
                        if (is_wp_error($response)) { return $response; }
                        if (str_contains(wp_strip_all_tags(wp_unslash($_SERVER["HTTP_ACCEPT_ENCODING"] ?? "")), "gzip") && function_exists("gzencode") && !(headers_sent() || ob_get_length() > 0)) {
                            $status  = ($response instanceof \WP_REST_Response) ? $response->get_status() : 200;
                            $data    = ($response instanceof \WP_REST_Response) ? $response->get_data() : $response;
                            $headers = ($response instanceof \WP_REST_Response) ? $response->get_headers() : ["Content-Type" => "application/json; charset=utf-8"];
                            $response = new \WP_REST_Response(gzencode(\NeoAnimate\NeoGlobal\json_encode_better($data), level: 1), status: $status, headers: array_merge($headers, ["Content-Encoding" => "gzip", "Vary" => "Accept-Encoding", "X-Neo-Raw-neo-animate" => "gzip"]));
                        }
                        return $response;
                    } catch (\Throwable $e) {
                        $rest_status = $e instanceof \NeoAnimate\NeoGlobal\GlobalException ? $e->get_status_code() : 500;
                        $message = $e instanceof \NeoAnimate\NeoGlobal\GlobalException ? $e->get_original_message() : $e->getMessage();
                        $debug_message = $e->getMessage(); if (WP_DEBUG) { $debug_message .= "\n\n" . $e->getTraceAsString(); }
                        return new \WP_Error($e instanceof \NeoAnimate\NeoGlobal\GlobalException ? $e->get_error_code() : "neo_global__endpoint_error", $message, ["status" => $rest_status, "debugMessage" => $debug_message]);
                    } finally { \NeoAnimate\NeoGlobal\performance_checkpoint("endpoint $http_method $path", is_end: true); }
                },
            ]);
        }
    });

    $register_rest_endpoint_for_js = function () use (&$path, &$permission_callback) { \NeoAnimate\NeoGlobal\val("neo_global__rest_endpoints")[$path] = $permission_callback() === "public"; };
    if (did_action("init") || doing_action("init")) { $register_rest_endpoint_for_js(); } else { \NeoAnimate\NeoGlobal\add_action_hook("init", $register_rest_endpoint_for_js); }
}

function get_rest_endpoint_url($path) {
    if (!did_action("init")) { \NeoAnimate\NeoGlobal\throw_global_exception("WP is not initialized yet. Cannot use the WP function for rest_url(). Please call this function after the 'init' action."); }
    if (!str_starts_with($path, "/wp-json/neo/")) { \NeoAnimate\NeoGlobal\throw_global_exception("The endpoint path must start with '/wp-json/neo/'."); }
    $path = str_replace("/wp-json/", "", $path);
    return rest_url($path);
}

\NeoAnimate\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoAnimate\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
        $rest_urls = [];
        foreach (\NeoAnimate\NeoGlobal\val("neo_global__rest_endpoints") as $path => $is_public) {
            $rest_urls[$path] = ["isPublic" => $is_public, "url" => get_rest_endpoint_url($path)];
        }

        \NeoAnimate\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalRestEndpoints", $rest_urls);
        \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoGlobalRestEndpointAjaxUrl", admin_url("admin-ajax.php"));
        if (\NeoAnimate\NeoGlobal\current_user_can__global_endpoint__refresh_nonce()) {
            \NeoAnimate\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoGlobalRestEndpointNonce", wp_create_nonce("wp_rest"));
        }
    });
});

// Output responses that require compression directly. This is safe because only structured JSON data is sent with proper headers (see above).
\NeoAnimate\NeoGlobal\add_filter_hook("rest_pre_serve_request", function ($served, $result) {
    if (!($result instanceof \WP_HTTP_Response)) { return $served; }
    $headers_lowercase = array_change_key_case($result->get_headers(), CASE_LOWER);
    if (($headers_lowercase["x-neo-raw-neo-animate"] ?? null) !== "gzip") { return $served; }
    foreach ($result->get_headers() as $name => $value) { header("{$name}: {$value}"); }

    echo $result->get_data(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ // The endpoint outputs gzipped data via echo for optimal performance and bandwidth efficiency. It returns structured data only (e.g., JSON) with proper headers (Content-Type: application/json, Content-Encoding: gzip). This approach ensures correct delivery, prevents code execution, and maintains full compatibility with gzip compression, which standard WordPress response handling would otherwise disrupt.
    return true;
});
