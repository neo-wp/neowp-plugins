<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

// Autoloader for Web Components used in the plugin for scoping styles and scripts from the WordPress backend.
function get_web_component_names_for_importer() {
    $web_components = [];
    foreach (\NeoReplace\NeoGlobal\fs_glob(\NeoReplace\NeoGlobal\plugin_path() . "/_global-web-component-*.js") ?: [] as $file) {
        $file = basename($file);
        if ($file === "_global-web-component-importer.js" || $file === "_global-web-component-importer.php") { continue; }
        $web_component_name = str_replace(["_global-web-component-", ".js"], ["", ""], $file);
        $web_component_name .= "-neo-replace";
        $web_components[] = $web_component_name;
    }
    return $web_components;
}
\NeoReplace\NeoGlobal\callback_before_enqueue_js_variables_backend_or_frontend(function () {
    if (!is_admin()) { return; }
    \NeoReplace\NeoGlobal\enqueue_js_variable_backend("neoGlobalWebComponentImporterWebComponents", get_web_component_names_for_importer());
});

\NeoReplace\NeoGlobal\add_action_hook("current_screen", function () {
    foreach (get_web_component_names_for_importer() as $web_component_name) {
        \NeoReplace\NeoGlobal\add_action_hook("admin_head", function () use ($web_component_name) {
            ?>
            <?php \NeoReplace\NeoGlobal\backend_page_style_tag_start(["id" => \wp_specialchars_decode(esc_attr("neo-web-component-hide-neo-replace-" . $web_component_name . "-inline-css"), ENT_QUOTES)]); ?>
                <?php echo esc_attr($web_component_name) ?> { <?php echo $web_component_name === "neo-info-tooltip-neo-replace" ? "display: none;" : "visibility: hidden" ?> }
            <?php \NeoReplace\NeoGlobal\backend_page_style_tag_end(); ?><?php
        });
    }
    \NeoReplace\NeoGlobal\enqueue_js("_global-web-component-importer.js");
});
