<?php
namespace NeoAlt\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

class GlobalException extends \Exception {
    private $status_code;
    private $error_code;
    private $original_message;

    public function __construct($message, $status_code = 500, $error_code = "neo_global__endpoint_error") {
        if (\function_exists(__NAMESPACE__ . "\\is_interface_system_available") && \NeoAlt\NeoGlobal\is_interface_system_available()) { [$additional_message_for_errors, $interface_ok] = \NeoAlt\NeoGlobal\call_interface_func_implemented('\NeoAlt\NeoLog\interface_additional_message_for_errors_20250505')(); } else { $interface_ok = false; }
        $message = esc_html($message); // Escape error message to avoid security vulnerabilities
        $this->original_message = $message;
        parent::__construct($message . ($interface_ok ? (" - " . $additional_message_for_errors) : ""), 0, null);
        $this->status_code = $status_code;
        $this->error_code = $error_code;

        if (\function_exists(__NAMESPACE__ . "\\global_log")) { \NeoAlt\NeoGlobal\global_log_with_module_name("_global--exceptions", "$message in " . $this->getTraceAsString()); \NeoAlt\NeoGlobal\global_log_with_module_name("_global--exceptions", "Error: $message in " . $this->getTraceAsString(), log_source: "error"); }
    }

    public function get_status_code() { return $this->status_code; }
    public function get_error_code() { return $this->error_code; }
    public function get_original_message() { return $this->original_message; }
}

class CurlException extends \NeoAlt\NeoGlobal\GlobalException {
    private $url;
    private $http_status_code;
    private $response_body;

    public function __construct($url, $http_status_code, $response_body) {
        $this->url = (string) $url;
        $this->http_status_code = (int) $http_status_code;
        $this->response_body = (string) $response_body;
        parent::__construct("Curl server error for URL " . $this->url . ": " . $this->http_status_code . "(response: " . $this->response_body . ")", status_code: 500, error_code: "neo_global__curl_error");
    }

    public function get_url() { return $this->url; }
    public function get_http_status_code() { return $this->http_status_code; }
    public function get_response_body() { return $this->response_body; }
}

function throw_global_exception($message, $status_code = 500, $error_code = "neo_global__endpoint_error") {
    throw new \NeoAlt\NeoGlobal\GlobalException($message, $status_code, $error_code); /* phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped */ /* Our custom GlobalException class above already escapes all inputs properly. */
}

function throw_curl_exception($url, $http_status_code, $response_body) {
    throw new \NeoAlt\NeoGlobal\CurlException($url, $http_status_code, $response_body); /* phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped */ /* Our custom CurlException class above already escapes all inputs properly through GlobalException. */
}
