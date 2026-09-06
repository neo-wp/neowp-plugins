<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Central point to enqueue CSS files using the standard WordPress functions
function enqueue_css($relative_path) {
    if (\NeoAnimate\NeoGlobal\val("neo_global__enqueue_disallowed") !== null) { \NeoAnimate\NeoGlobal\throw_global_exception("Enqueueing CSS files is disallowed in this section of code: " . \NeoAnimate\NeoGlobal\val("neo_global__enqueue_disallowed")); }
    wp_enqueue_style(
        handle: "neo-animate--" . \NeoAnimate\NeoGlobal\preg_replace_better('/[^a-zA-Z0-9]+/', "-", $relative_path),
        src: \NeoAnimate\NeoGlobal\plugin_url() . "/" . $relative_path,
        deps: [],
        ver: "1",
    );
}

// Central point to enqueue JS files using the standard WordPress functions
function enqueue_js($relative_path, $dependencies=[]) {
    if (\NeoAnimate\NeoGlobal\val("neo_global__enqueue_disallowed") !== null) { \NeoAnimate\NeoGlobal\throw_global_exception("Enqueueing JS files is disallowed in this section of code: " . \NeoAnimate\NeoGlobal\val("neo_global__enqueue_disallowed")); }
    if (did_action("parse_query") && \function_exists("amp_is_request") && \amp_is_request()) { return; }
    $add_type_module = empty($dependencies);
    wp_register_script(_enqueue_js_variable_handle(), false, [], "1", false); $dependencies[] = _enqueue_js_variable_handle();
    wp_enqueue_script(
        "neo-animate--" . ($add_type_module ? "module--" : "") . \NeoAnimate\NeoGlobal\preg_replace_better('/[^a-zA-Z0-9]+/', "-", $relative_path),
        \NeoAnimate\NeoGlobal\plugin_url() . "/" . $relative_path,
        $dependencies,
        "1",
        false,
    );
}

\NeoAnimate\NeoGlobal\add_filter_hook("script_loader_tag", function ($tag, $handle, $src) {
    if (!str_starts_with($handle, "neo-animate--")) { return $tag; }

    $script_var_name = str_replace("-", "_", $handle) . "_script";

    $tag = "<scr" . "ipt>" . /* Break up script tag to avoid false positive auto-detection because the wp_enqueue_script function is properly used */
            "const " . $script_var_name . ' = document.createElement("script");' .
            (str_contains($handle, "module--") ? ($script_var_name . '.type = "module";') : "") .
            $script_var_name . '.src = "' . esc_url($src) . '";' .
            "document.head.appendChild(" . $script_var_name . ");" .
        "</scr" . "ipt>"; /* Break up script tag to avoid false positive auto-detection because the wp_enqueue_script function is properly used */
    return $tag;
});

\NeoAnimate\NeoGlobal\val("neo_global__enqueue_disallowed", null);
function disallow_enqueue($reason_string, $callback) {
    try {
        \NeoAnimate\NeoGlobal\val("neo_global__enqueue_disallowed", $reason_string);
        $callback();
    } finally { \NeoAnimate\NeoGlobal\val("neo_global__enqueue_disallowed", null); }
}

// Central point to enqueue JS variables using standard WordPress functions
\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables", []);
function _enqueue_js_variable_handle() { return "neo_global__enqueue_js_variable_neo-animate"; }
function _enqueue_js_var($backend_or_frontend, $var_name, $var_value) {
    if ((\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables")[$backend_or_frontend] ?? null) === "#neoEnqueueTooLate") { \NeoAnimate\NeoGlobal\throw_global_exception("The JS variable '$var_name' cannot be registered for the $backend_or_frontend because the corresponding enqueue action has already been run. Please register the variable earlier."); }
    if (!\NeoAnimate\NeoGlobal\preg_match_better('/^[a-zA-Z][a-zA-Z0-9_]*$/', $var_name)) { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid JS variable name '$var_name'."); }
    if (isset(\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables")[$backend_or_frontend][$var_name])) {
        if (\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables")[$backend_or_frontend][$var_name] === $var_value) { return; }
        \NeoAnimate\NeoGlobal\throw_global_exception("The JS variable '$var_name' has already been registered for the $backend_or_frontend with a different value. Please use a unique name for each variable.");
    }

    \NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables")[$backend_or_frontend][$var_name] = $var_value;
}
function enqueue_js_variable_backend             ($var_name, $var_value) { _enqueue_js_var("backend",  $var_name, $var_value); }
function enqueue_js_variable_frontend            ($var_name, $var_value) { _enqueue_js_var("frontend", $var_name, $var_value); }
function enqueue_js_variable_backend_and_frontend($var_name, $var_value) { enqueue_js_variable_backend($var_name, $var_value); enqueue_js_variable_frontend($var_name, $var_value); }

\NeoAnimate\NeoGlobal\val("neo_global__enqueue_inline_before_callbacks", []);
function callback_before_enqueue_js_variables_backend_or_frontend($callback) {
    foreach (["backend", "frontend"] as $backend_or_frontend) {
        if ((\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables")[$backend_or_frontend] ?? null) === "#neoEnqueueTooLate") { \NeoAnimate\NeoGlobal\throw_global_exception("The callback cannot be registered because the '$backend_or_frontend' JS variables have already been enqueued. Please register the callback earlier."); }
        \NeoAnimate\NeoGlobal\val("neo_global__enqueue_inline_before_callbacks")[$backend_or_frontend][] = $callback;
    }
}

function get_code_for_js_variables($backend_or_frontend) {
    if (!in_array($backend_or_frontend, ["backend", "frontend"])) { \NeoAnimate\NeoGlobal\throw_global_exception("Invalid parameter 'backend_or_frontend': '$backend_or_frontend'."); }
    foreach (\NeoAnimate\NeoGlobal\val("neo_global__enqueue_inline_before_callbacks")[$backend_or_frontend] ?? [] as $callback) { $callback(); }
    \NeoAnimate\NeoGlobal\val("neo_global__enqueue_inline_before_callbacks")[$backend_or_frontend] = [];
    $js_variable_container = "neoGlobalEnqueue" . ucfirst($backend_or_frontend) . "NeoAnimate";
    $code = "/* Enqueued JS Variablen */ window.$js_variable_container = {...(window.$js_variable_container ?? {}), "; foreach (\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables")[$backend_or_frontend] ?? [] as $js_var_name => $value) { $code .= "'$js_var_name': " . \NeoAnimate\NeoGlobal\php_to_js_object($value) . ", "; } $code .= "};";
    return $code;
}

\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables_context_was_enqueued", null);
(function () {
    foreach (["backend", "frontend"] as $backend_or_frontend) {
        $enqueue_variables = function () use ($backend_or_frontend) {
            if ($backend_or_frontend === "frontend" && \function_exists("amp_is_request") && \amp_is_request()) { return; }
            if ((\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables")[$backend_or_frontend] ?? null) === "#neoEnqueueTooLate") { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global--enqueue-loader", "JS variables for '$backend_or_frontend' were enqueued twice in the same request. This likely means that multiple enqueue hooks fired, e.g. admin_enqueue_scripts and elementor/editor/before_enqueue_scripts."); return; }
            if (\NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables_context_was_enqueued") !== null && \NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables_context_was_enqueued") !== $backend_or_frontend) { \NeoAnimate\NeoGlobal\global_warn_with_module_name("_global--enqueue-loader", "JS variables for '$backend_or_frontend' were skipped because JS variables for '" . \NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables_context_was_enqueued") . "' were already enqueued in the same request. This likely means that a plugin triggered both backend and frontend enqueue hooks, e.g. Jetpack Sync in a cronjob."); return; }
            \NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables_context_was_enqueued", $backend_or_frontend);
            $handle = _enqueue_js_variable_handle();
            wp_register_script($handle, false, [], "1", false); wp_enqueue_script($handle);
            wp_add_inline_script($handle, get_code_for_js_variables($backend_or_frontend), "before");
            \NeoAnimate\NeoGlobal\val("neo_global__enqueue_js_variables")[$backend_or_frontend] = "#neoEnqueueTooLate";
        };
        if ($backend_or_frontend === "backend")  { \NeoAnimate\NeoGlobal\add_action_hook("admin_enqueue_scripts:0", "elementor/editor/before_enqueue_scripts:0", $enqueue_variables); }
        if ($backend_or_frontend === "frontend") { \NeoAnimate\NeoGlobal\add_action_hook("wp_enqueue_scripts:0", $enqueue_variables); }
    }
})();
