<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Preparation of a translation system that will soon provide translations of all languages worldwide in the correct context.
function neo__($en_str, $de_str) {
    if (str_starts_with(get_locale(), "de_")) { return $de_str; }
    return $en_str;
}

function echo_neo__($en_str, $de_str) {
    echo wp_kses(neo__($en_str, $de_str), [
        "a" => [ "href" => [], "title" => [], "target" => [], "rel" => [] ],
        "br" => [],
        "strong" => [], "b" => [],
        "em" => [], "i" => [],
        "span" => [ "class" => [] ],
        "code" => [],
    ]);
}

function echo_neo_attr__($en_str, $de_str) { echo esc_attr(neo__($en_str, $de_str)); }
