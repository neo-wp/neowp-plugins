<?php
namespace NeoRename\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function pagebuilder_warning_detection_script() {?>
    <?php \NeoRename\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
        const channel = new BroadcastChannel("neo-global--pagebuilder-warning-check-neo-rename");
        channel.onmessage = (event) => {
            if (event?.data?.type === "is-pagebuilder-open") {
                channel.postMessage({ type: "pagebuilder-response", url: location.href });
            }
        };
    <?php \NeoRename\NeoGlobal\backend_page_script_tag_end(); ?>
<?php }

\NeoRename\NeoGlobal\add_action_hook("admin_footer", function () { if (!(get_current_screen() && get_current_screen()->is_block_editor)) { return; }pagebuilder_warning_detection_script(); });
\NeoRename\NeoGlobal\add_action_hook("elementor/editor/after_enqueue_scripts", function () { \NeoRename\NeoGlobal\add_action_hook("admin_print_footer_scripts",function () { pagebuilder_warning_detection_script(); }); });
\NeoRename\NeoGlobal\add_action_hook("wp_footer", function () { if (!(\NeoRename\NeoGlobal\query_param("bricks") === "run" && \NeoRename\NeoGlobal\query_param("brickspreview") !== null) && !(\NeoRename\NeoGlobal\query_param("action") !== null && \NeoRename\NeoGlobal\query_param("action") === "bricks_edit")) { return; } pagebuilder_warning_detection_script(); });
\NeoRename\NeoGlobal\add_action_hook("admin_footer", function () { $s = function_exists("get_current_screen") ? get_current_screen() : null; if (!($s && $s->base==="post" && empty($s->is_block_editor))){ return; }                                                                    pagebuilder_warning_detection_script(); });
