<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function request_uri() {
    return esc_url_raw(wp_unslash($_SERVER["REQUEST_URI"] ?? ""));
}

class NeoURL {
    private $schema           = null;
    private $host             = null;
    private $path             = [];
    private $params           = [];
    private $fragment         = null;

    private $invalid_original = null;

    public function __construct($url) {
        $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
        $parsed = wp_parse_url($url);
        if ($parsed === false) { $this->invalid_original = $url; return; }

        $this->schema = $parsed["scheme"] ?? null;
        $this->host = isset($parsed["host"]) ? ( (isset($parsed["user"]) ? $parsed["user"] . (isset($parsed["pass"]) ? ":" . $parsed["pass"] : "") . "@" : "") . $parsed["host"] . (isset($parsed["port"]) ? ":" . $parsed["port"] : "") ) : null;
        if (str_starts_with($url, $this->schema . "://") && $this->host === null) { $this->host = ""; }
        $this->path = ($parsed["path"] ?? "") === "" ? [] : explode("/", $parsed["path"] ?? ""); if (!empty($this->path) && substr_count($parsed["path"] ?? "", "/") === strlen($parsed["path"] ?? "")) { array_pop($this->path); }

        $this->params = [];
        if (isset($parsed["query"])) {
            $segments = explode("&", $parsed["query"]);
            foreach ($segments as $segment) {
                $key = ""; $value = null;
                if ($segment === "") { $this->params[] = ["", null]; continue; }
                $parts = explode("=", $segment, 2);
                $key = $parts[0];
                if (count($parts) === 2) { $value = $parts[1]; }
                else { $value = null; }
                $this->params[] = [$key, $value];
            }
        }
        $this->fragment = $parsed["fragment"] ?? null;
    }

    public function to_string() {
        if ($this->invalid_original !== null) { return $this->invalid_original; }
        $url = "";
        $url .= $this->schema ? ($this->schema . ":") : "";
        $url .= $this->host !== null ? ("//" . $this->host) : "";
        if ($this->host !== null && !empty($this->path) && $this->path[0] !== "") { $url .= "/"; }
        $path_parts = $this->path; if (!empty($path_parts) && count(\NeoRename\NeoGlobal\array_filter_better($path_parts, fn ($p) => $p !== "")) === 0) { $path_parts[]= ""; }
        $url .= !empty($path_parts) ? implode("/", $path_parts) : "";
        if (!empty($this->params)) {
            $url .= "?";
            foreach ($this->params as $index => [$key, $value]) {
                if ($index > 0) { $url .= "&"; }
                $url .= $key;
                if ($value !== null) { $url .= "=" . $value; }
            }
        }
        if ($this->fragment !== null) { $url .= "#" . $this->fragment; }
        return $url;
    }

    public function is_valid() {
        return $this->invalid_original === null;
    }

    public function is_absolute() {
        return ($this->schema !== null) || ($this->host !== null);
    }

    public function make_relative() {
        $this->schema = null; $this->host = null;
        return $this;
    }

    public function only_keep_schema() {
        $this->host = null; $this->path = []; $this->params = []; $this->fragment = null;
        return $this;
    }

    public function only_keep_host() {
        $this->schema = null; $this->path = []; $this->params = []; $this->fragment = null;
        return $this;
    }

    public function only_keep_path() {
        $this->schema = null; $this->host = null; $this->params = []; $this->fragment = null;
        return $this;
    }

    public function remove_www_from_host() {
        if ($this->host !== null && str_starts_with(strtolower($this->host), "www.")) { $this->host = substr($this->host, 4); }
        return $this;
    }

    public function lowercase_schema_and_host() {
        if ($this->schema !== null) { $this->schema = strtolower($this->schema); }
        if ($this->host   !== null) {
            $at_pos = strrpos($this->host, "@") ?: 0;
            $this->host = substr($this->host, 0, $at_pos) . strtolower(substr($this->host, $at_pos));
        }
        return $this;
    }

    public function trailingslash_path() {
        if (!in_array(strtolower($this->schema ?? ""), ["http", "https", ""])) { return $this; }
        if (str_contains(end($this->path) ?: "", ".")) { return $this; }
        foreach ($this->path as $segment) { if (str_starts_with($segment, "wp-")) { return $this; } }
        if (empty($this->path)) { $this->path = [""]; return $this; }
        if (end($this->path) !== "") { $this->path[] = ""; }
        return $this;
    }

    public function take_missing_schema_and_host($url) {
        $url_object = new NeoURL($url);
        $this->schema ??= $url_object->schema;
        $this->host ??= $url_object->host;
        return $this;
    }

    public function get_query_param($key) {
        for ($i = count($this->params) - 1; $i >= 0; $i--) {
            if ($this->params[$i][0] === $key || urldecode($this->params[$i][0]) === $key) { return urldecode($this->params[$i][1] ?? ""); }
        }
        return null;
    }

    public function remove_fragment() { $this->fragment = null; return $this; }

    public function add_or_update_query_params($params) {
        foreach ($params as $k => $v) {
            $found = false;
            for ($i = count($this->params) - 1; $i >= 0; $i--) {
                if ($this->params[$i][0] === $k || urldecode($this->params[$i][0]) === $k) {
                    $this->params[$i][1] = ($v === null) ? null : urlencode($v);
                    $found = true;
                    break;
                }
            }
            if (!$found) { $this->params[] = [urlencode($k), ($v === null) ? null : urlencode($v)]; }
        }
        return $this;
    }

    public function remove_params($keys) {
        $this->params = \NeoRename\NeoGlobal\array_filter_better($this->params, function ($param) use ($keys) { return !(in_array($param[0], $keys) || in_array(urldecode($param[0]), $keys)); });
        return $this;
    }

    public function remove_all_params() {
        $this->params = [];
        return $this;
    }
}

function get_url_path($url) {
    return (new NeoURL($url))->only_keep_path()->to_string();
}

function get_url_with_correct_slash($url) {
    return (new NeoURL($url))->trailingslash_path()->to_string();
}

function is_url_absolute($url) {
    return (new NeoURL($url))->is_absolute();
}

function is_url_internal($url) {
    static $site_url_schema = null; $site_url_schema ??= (new NeoURL(get_site_url()))->lowercase_schema_and_host()->only_keep_schema()->to_string();
                                    $url_schema        = (new NeoURL($url))          ->lowercase_schema_and_host()->only_keep_schema()->to_string() ?: $site_url_schema;
    if ($site_url_schema === "http:") { $site_url_schema = "https:"; }
    if ($url_schema      === "http:") { $url_schema      = "https:"; }
    static $site_url_host = null; $site_url_host ??= (new NeoURL(get_site_url()))->lowercase_schema_and_host()->remove_www_from_host()->only_keep_host()->to_string();
                                  $url_host        = (new NeoURL($url))          ->lowercase_schema_and_host()->remove_www_from_host()->only_keep_host()->to_string() ?: $site_url_host;
    return ($site_url_schema === $url_schema && $site_url_host === $url_host);
}

function make_url_absolute($url) {
    if (!is_url_internal($url)) { return $url; }
    return (new NeoURL($url))->take_missing_schema_and_host(get_site_url())->to_string();
}

function make_internal_url_relative($url) {
    if (!is_url_internal($url)) { return $url; }
    return (new NeoURL($url))->make_relative()->to_string();
}

function make_internal_url_relative_to_uploads($img_url) {
    if (!is_url_internal($img_url)) { return $img_url; }

    static $relative_uploads_url = null; $relative_uploads_url ??= (new NeoURL(\NeoRename\NeoGlobal\uploads_url()))->make_relative()->to_string();
                                         $relative_img_url       = (new NeoURL($img_url))                ->make_relative()->to_string();
    return \NeoRename\NeoGlobal\str_replace_start(
        $relative_uploads_url . "/", "",
        $relative_img_url
    );
}

function is_url_in_uploads_folder($img_url) {
    if (!is_url_internal($img_url)) { return false; }
    static $relative_uploads_url = null; $relative_uploads_url ??= (new NeoURL(\NeoRename\NeoGlobal\uploads_url()))->make_relative()->to_string();
                                         $relative_img_url       = (new NeoURL($img_url))                ->make_relative()->to_string();
    return str_starts_with(
        $relative_img_url,
        $relative_uploads_url . "/",
    );
}

function get_query_param_from_url($url, $param) {
    return (new NeoURL($url))->get_query_param($param);
}

function query_param($param) {
    return get_query_param_from_url(request_uri(), $param);
}

function add_or_update_query_params($url, $params) {
    return (new NeoURL($url))->add_or_update_query_params($params)->to_string();
}
function add_query_param(string $url, string $key, ?string $value = null): string {
    return add_or_update_query_params($url, [$key => $value]);
}

function remove_query_param($url, $key) {
    return (new NeoURL($url))->remove_params([$key])->to_string();
}

function remove_all_query_params($url) {
    return (new NeoURL($url))->remove_all_params()->to_string();
}

function remove_fragment($url) { return (new NeoURL($url))->remove_fragment()->to_string(); }

function percent_encode_invalid_utf8_url_bytes(string $url): string {
    if (mb_check_encoding($url, "UTF-8")) { return $url; }
    $url = str_replace("%", "%25", $url);
    return \NeoRename\NeoGlobal\preg_replace_callback_better("/[\\x80-\\xFF]/", fn ($byte) => "%" . strtoupper(bin2hex($byte[0])), $url);
}

function percent_decode_invalid_utf8_url_bytes(string $url): string {
    $decoded_url = rawurldecode($url);
    return !mb_check_encoding($decoded_url, "UTF-8") ? $decoded_url : $url;
}

function percent_encode_invalid_utf8_bytes_recursive($value) {
    if (is_string($value)) { return mb_check_encoding($value, "UTF-8") ? $value : \NeoRename\NeoGlobal\preg_replace_callback_better("/[\\x80-\\xFF]/", fn ($byte) => "%" . strtoupper(bin2hex($byte[0])), $value); }
    if (!is_array($value)) { return $value; }
    $encoded_value = [];
    foreach ($value as $key => $item) { $encoded_value[is_string($key) ? percent_encode_invalid_utf8_url_bytes($key) : $key] = percent_encode_invalid_utf8_bytes_recursive($item); }
    return $encoded_value;
}

function get_path_from_internal_url($url) {
    if (!is_url_internal($url)) { \NeoRename\NeoGlobal\throw_global_exception("No internal URL: " . $url); }
    $relative_url_path = make_internal_url_relative(remove_fragment(remove_all_query_params($url))); $relative_site_url_path = make_internal_url_relative(get_site_url()); if ($relative_site_url_path !== "" && str_starts_with($relative_url_path . "/", untrailingslashit($relative_site_url_path) . "/")) { $relative_url_path = substr($relative_url_path, strlen(untrailingslashit($relative_site_url_path))); }
    return trailingslashit(ABSPATH) . ltrim($relative_url_path, "/"); /* Usage of ABSPATH is unavoidable because there is no other robust way to resolve any image paths within the WP instance. Callers are always robust against other paths as well. #suppressLinterWporgDirectoryConstantCheck */
}
