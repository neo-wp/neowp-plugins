<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Autoloader for Web Components used in the plugin for scoping styles and scripts from the WordPress backend.
function get_web_component_names_for_importer() {
    $web_components = [];
    foreach (\NeoAnimate\NeoGlobal\fs_glob(\NeoAnimate\NeoGlobal\plugin_path() . "/_global-web-component-*.js") ?: [] as $file) {
        $file = basename($file);
        if ($file === "_global-web-component-importer.js" || $file === "_global-web-component-importer.php") { continue; }
        $web_component_name = str_replace(["_global-web-component-", ".js"], ["", ""], $file);
        $web_component_name .= "-neo-animate";
        $web_components[] = $web_component_name;
    }
    return $web_components;
}
\NeoAnimate\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    if (!is_admin()) { return; }
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoGlobalWebComponentImporterWebComponents", get_web_component_names_for_importer());
});

\NeoAnimate\NeoGlobal\add_action_hook("current_screen", function () {
    foreach (get_web_component_names_for_importer() as $web_component_name) {
        \NeoAnimate\NeoGlobal\add_action_hook("admin_head", function () use ($web_component_name) {
            ?>
            <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_start(["id" => \wp_specialchars_decode(esc_attr("neo-web-component-hide-neo-animate-" . $web_component_name . "-inline-css"), ENT_QUOTES)]); ?>
                <?php echo esc_attr($web_component_name) ?> { <?php echo $web_component_name === "neo-info-tooltip-neo-animate" ? "display: none;" : "visibility: hidden" ?> }
            <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_end(); ?><?php
        });
    }
    \NeoAnimate\NeoGlobal\enqueue_js("_global-web-component-importer.js");
});
