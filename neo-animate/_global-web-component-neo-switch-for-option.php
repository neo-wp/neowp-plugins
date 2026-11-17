<?php
namespace NeoAnimate\NeoGlobal; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

function echo_switch_for_option($option_name, $label_text = "", $only_enabled_if_pro = false, $js_function_on_change = null) {
    ?><!-- Schalter für Option <?php echo esc_attr($option_name) ?> --><?php 
    $is_checked = \NeoAnimate\NeoGlobal\get_or_set_option($option_name);
    if ($only_enabled_if_pro) { $is_checked = false; }
    ?><neo-switch-neo-animate 
        data-switch-for-option-name="<?php echo esc_attr($option_name)?>"
        <?php if ($is_checked) { echo " checked"; }?>
        <?php if ($only_enabled_if_pro) { echo " only-enabled-if-pro"; }?>
        label="<?php echo esc_attr($label_text);?>"
        onchange="return window.neoChangeSwitchForOptionNeoAnimate(this, <?php echo esc_attr($js_function_on_change ?? 'null')?>)"
    ></neo-switch-neo-animate><?php
    \NeoAnimate\NeoGlobal\add_action_hook("admin_footer", function () {
        \NeoAnimate\NeoGlobal\enqueue_js("_global-web-component-neo-switch-for-option.js");
    });
}
