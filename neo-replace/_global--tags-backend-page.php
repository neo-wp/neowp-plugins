<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

//  These direct tags are used only on our fully controlled backend pages, where WordPress enqueue functions cannot output assets at the required position.
function backend_page_script_tag_start(array $attributes = []): void {
    $validated_attributes = [];
    foreach ($attributes as $attribute_name => $attribute_value) {
        if (!(is_string($attribute_name) && \NeoReplace\NeoGlobal\preg_match_better('/^[a-zA-Z][a-zA-Z0-9:-]*$/', $attribute_name))) { \NeoReplace\NeoGlobal\throw_global_exception("Invalid backend page script attribute name: " . $attribute_name); }
        if ($attribute_value === null || $attribute_value === false) { continue; }
        if ($attribute_value === true) { $validated_attributes[$attribute_name] = true; continue; }
        if (!is_scalar($attribute_value)) { \NeoReplace\NeoGlobal\throw_global_exception("Invalid backend page script attribute value for '" . $attribute_name . "'."); }
        $validated_attributes[$attribute_name] = (string)$attribute_value;
    }
    _backend_page_tag_buffer_start("script", $validated_attributes);
}

function backend_page_script_tag_end(): void {
    [$tag_content, $attributes] = _backend_page_tag_buffer_end("script");
    wp_print_inline_script_tag($tag_content, $attributes);
}

function backend_page_style_tag_start(array $attributes = []): void {
    if (\NeoReplace\NeoGlobal\array_diff_better(array_keys($attributes), ["id"])) { \NeoReplace\NeoGlobal\throw_global_exception("Backend page style tags only support the id attribute."); }
    if (isset($attributes["id"]) && !(is_string($attributes["id"]) && \NeoReplace\NeoGlobal\preg_match_better('/^[a-zA-Z][a-zA-Z0-9_-]*-inline-css$/', $attributes["id"]))) { \NeoReplace\NeoGlobal\throw_global_exception("Invalid backend page style id attribute. Style IDs must end with -inline-css: " . $attributes["id"]); }
    _backend_page_tag_buffer_start("style", $attributes);
}

function backend_page_style_tag_end(): void {
    [$tag_content, $attributes] = _backend_page_tag_buffer_end("style");
    static $style_counter = 0;
    $style_counter++;
    $handle = isset($attributes["id"]) ? \NeoReplace\NeoGlobal\preg_replace_better('/-inline-css$/', "", $attributes["id"], 1) : "neo-replace--backend-page-inline-style-" . $style_counter;
    global $wp_styles;
    $global_wp_styles_to_do_count = isset($wp_styles) ? count($wp_styles->to_do ?? []) : null;
    $styles = new \WP_Styles();
    $styles->add($handle, false, [], "1");
    $styles->enqueue($handle);
    $styles->add_inline_style($handle, $tag_content);
    $styles->do_items([$handle]);
    if ($global_wp_styles_to_do_count !== (isset($wp_styles) ? count($wp_styles->to_do ?? []) : null)) { \NeoReplace\NeoGlobal\throw_global_exception("Local backend page style output unexpectedly changed the global WP_Styles queue."); }
}

function backend_page_stylesheet_link_tag(array $attributes): void {
    if (!(($attributes["rel"] ?? null) === "stylesheet" && isset($attributes["href"]) && is_string($attributes["href"]))) { \NeoReplace\NeoGlobal\throw_global_exception("Invalid backend page stylesheet link attributes."); }
    if (\NeoReplace\NeoGlobal\array_diff_better(array_keys($attributes), ["rel", "href", "media"])) { \NeoReplace\NeoGlobal\throw_global_exception("Backend page stylesheet link tags only support rel, href and media attributes."); }
    if (isset($attributes["media"]) && !is_string($attributes["media"])) { \NeoReplace\NeoGlobal\throw_global_exception("Invalid backend page stylesheet link media attribute."); }
    static $stylesheet_counter = 0;
    $stylesheet_counter++;
    $handle = "neo-replace--backend-page-stylesheet-" . $stylesheet_counter;
    global $wp_styles;
    $global_wp_styles_to_do_count = isset($wp_styles) ? count($wp_styles->to_do ?? []) : null;
    $styles = new \WP_Styles();
    $styles->add($handle, $attributes["href"], [], null, $attributes["media"] ?? "all");
    $styles->enqueue($handle);
    $styles->do_items([$handle]);
    if ($global_wp_styles_to_do_count !== (isset($wp_styles) ? count($wp_styles->to_do ?? []) : null)) { \NeoReplace\NeoGlobal\throw_global_exception("Local backend page stylesheet output unexpectedly changed the global WP_Styles queue."); }
}

function _backend_page_tag_buffer_start(string $tag_name, array $attributes): void {
    if (\NeoReplace\NeoGlobal\val("neo_global__backend_page_" . $tag_name . "_tag_buffers") === null) { \NeoReplace\NeoGlobal\val("neo_global__backend_page_" . $tag_name . "_tag_buffers", []); }
    \NeoReplace\NeoGlobal\val("neo_global__backend_page_" . $tag_name . "_tag_buffers")[] = $attributes;
    ob_start();
}

function _backend_page_tag_buffer_end(string $tag_name): array {
    if (empty(\NeoReplace\NeoGlobal\val("neo_global__backend_page_" . $tag_name . "_tag_buffers"))) { \NeoReplace\NeoGlobal\throw_global_exception("Backend page " . $tag_name . " tag buffer was not started."); }
    $attributes = array_pop(\NeoReplace\NeoGlobal\val("neo_global__backend_page_" . $tag_name . "_tag_buffers"));
    return [ob_get_clean(), $attributes];
}
