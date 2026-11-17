<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("neo_init", function () {
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoDrawEditorUrl", \NeoAnimate\NeoGlobal\get_backend_page_url("neo-draw--editor"));
});

\NeoAnimate\NeoGlobal\register_backend_page("neo-draw--editor", fn () => \NeoAnimate\NeoGlobal\current_user_can__neo_draw(), function () {
    $neo_draw_editor_locale_parts = explode("_", get_locale());
    $neo_draw_editor_language_code = isset($neo_draw_editor_locale_parts[1]) ? strtolower($neo_draw_editor_locale_parts[0]) . "-" . strtoupper($neo_draw_editor_locale_parts[1]) : strtolower($neo_draw_editor_locale_parts[0]);
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoDrawEditorLanguageCode",        $neo_draw_editor_language_code);
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoDrawEditorIconLibraryUrl",      \NeoAnimate\NeoGlobal\get_backend_page_url("neo-draw--icon-library"));
    [$neo_draw_settings_section_url, $interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoSettings\interface_get_neo_settings_section_url_20260613')("neo-draw"); if (!$interface_ok) { $neo_draw_settings_section_url = ""; }
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoDrawEditorSettingsPageUrl",     $neo_draw_settings_section_url);
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoDrawEditorWpSiteUrl",           get_site_url());
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoDrawEditorPluginVersion",       \NeoAnimate\NeoGlobal\plugin_version());
    [$neo_animate_editor_head_code, $neo_animate_interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoAnimate\interface_get_neo_draw_editor_head_html_20260611')();
    [$neo_motion_editor_head_code, $neo_motion_interface_ok]   = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoMotion\interface_get_neo_draw_editor_head_html_20260615')();
    [$neo_animate_web_component_importer_url, $neo_animate_web_component_importer_interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoAnimate\interface_get_neo_draw_editor_web_component_importer_url_20260615')();
    [$neo_motion_web_component_importer_url,  $neo_motion_web_component_importer_interface_ok]  = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoMotion\interface_get_neo_draw_editor_web_component_importer_url_20260615')();
    [$neo_log_js_url, $neo_log_interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoLog\interface_get_neo_log_js_url_20260621')();
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoDrawAnimationIntegrationAvailable", $neo_animate_interface_ok);
    \NeoAnimate\NeoGlobal\enqueue_js_variable_backend("neoDrawMotionIntegrationAvailable",    $neo_motion_interface_ok);

    ?><!DOCTYPE html>
    <html>
        <head>
            <title>neoDraw Editor</title>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover, shrink-to-fit=no">
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start([]); ?>
                window.EXCALIDRAW_ASSET_PATH = "<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/neo-draw--excalidraw-thirdparty/";
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?>

            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start([]); ?>
                <?php echo \NeoAnimate\NeoGlobal\get_code_for_js_variables("backend") /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>; /* Output is properly escaped in get_code_for_js_variables() for security */
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?>
            <?php if ($neo_animate_interface_ok) {
                wp_print_inline_script_tag($neo_animate_editor_head_code); /* Output is properly escaped in get_code_for_js_variables() for security */
            } ?>
            <?php if ($neo_motion_interface_ok) {
                wp_print_inline_script_tag($neo_motion_editor_head_code); /* Output is properly escaped in get_code_for_js_variables() for security */
            } ?>

            <?php if ($neo_log_interface_ok) { ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url($neo_log_js_url), ENT_QUOTES), "type" => "module"]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><?php } ?> <!-- Script tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php \NeoAnimate\NeoGlobal\backend_page_stylesheet_link_tag(["rel" => "stylesheet", "href" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/neo-draw--editor-excalidraw-custom-styles.css"), ENT_QUOTES)]); ?><!-- Style tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet */ ?>
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/neo-draw--excalidraw-thirdparty/react.js"), ENT_QUOTES)]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><!-- Script tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/neo-draw--excalidraw-thirdparty/react-dom.js"), ENT_QUOTES)]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><!-- Script tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/neo-draw--editor-xml-formatter-thirdparty/neo-draw--editor-xml-formatter.min.js"), ENT_QUOTES)]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><!-- Script tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/_global-web-component-importer.js"), ENT_QUOTES), "type" => "module"]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php if ($neo_animate_web_component_importer_interface_ok) { ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url($neo_animate_web_component_importer_url), ENT_QUOTES), "type" => "module"]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><?php } ?>  <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php if ($neo_motion_web_component_importer_interface_ok) { ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url($neo_motion_web_component_importer_url), ENT_QUOTES), "type" => "module"]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><?php } ?>  <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/neo-draw--editor-bootstrap.js"), ENT_QUOTES), "type" => "module"]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><!-- Script tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
        </head>

        <body>
            <div id="app"></div>
        </body>
    </html><?php
});
