<?php
namespace NeoReplace\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function pagebuilder_warning_detection_script() {?>
    <?php \NeoReplace\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
        const channel = new BroadcastChannel("neo-global--pagebuilder-warning-check-neo-replace");
        channel.onmessage = (event) => {
            if (event?.data?.type === "is-pagebuilder-open") {
                channel.postMessage({ type: "pagebuilder-response", url: location.href });
            }
        };
    <?php \NeoReplace\NeoGlobal\backend_page_script_tag_end(); ?>
<?php }

\NeoReplace\NeoGlobal\add_action_hook("admin_footer", function () { if (!(get_current_screen() && get_current_screen()->is_block_editor)) { return; }pagebuilder_warning_detection_script(); });
\NeoReplace\NeoGlobal\add_action_hook("elementor/editor/after_enqueue_scripts", function () { \NeoReplace\NeoGlobal\add_action_hook("admin_print_footer_scripts",function () { pagebuilder_warning_detection_script(); }); });
\NeoReplace\NeoGlobal\add_action_hook("wp_footer", function () { if (!(\NeoReplace\NeoGlobal\query_param("bricks") === "run" && \NeoReplace\NeoGlobal\query_param("brickspreview") !== null) && !(\NeoReplace\NeoGlobal\query_param("action") !== null && \NeoReplace\NeoGlobal\query_param("action") === "bricks_edit")) { return; } pagebuilder_warning_detection_script(); });
\NeoReplace\NeoGlobal\add_action_hook("admin_footer", function () { $s = function_exists("get_current_screen") ? get_current_screen() : null; if (!($s && $s->base==="post" && empty($s->is_block_editor))){ return; }                                                                    pagebuilder_warning_detection_script(); });
