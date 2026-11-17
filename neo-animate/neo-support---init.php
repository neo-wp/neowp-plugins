<?php
namespace NeoAnimate\NeoSupport; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\add_action_hook("neo_init", function () {
    $settings_render_callback = function () {
        ?><neo-setting-neo-animate>
            <div slot="left">
                <h3><?php \NeoAnimate\NeoGlobal\echo_neo__("Contact Support", "Support kontaktieren") ?></h3>
                <p><?php \NeoAnimate\NeoGlobal\echo_neo__("Message us about what's on your mind. The neoWP team answers shortly.", "Schreib uns, was dir auf dem Herzen liegt. Das neoWP-Team antwortet in Kürze.") ?></p>
            </div>
            <div slot="right" id="neo-support--buttons">
                <neo-button-neo-animate href="<?php echo esc_url("https://" . \NeoAnimate\NeoGlobal\option__neo_wp_com() . \NeoAnimate\NeoGlobal\neo__("", "/de") .  "/contact/?ref=neo-support#free") ?>" target="_blank"><?php \NeoAnimate\NeoGlobal\echo_neo__("Contact us", "Kontaktiere uns") ?></neo-button-neo-animate>
                <neo-button-neo-animate href="<?php echo esc_url("https://" . \NeoAnimate\NeoGlobal\option__neo_wp_com() . \NeoAnimate\NeoGlobal\neo__("", "/de") .  "/contact/?ref=neo-support#pro") ?>"  target="_blank" only-enabled-if-pro><?php \NeoAnimate\NeoGlobal\echo_neo__("Contact Pro support", "Pro-Support kontaktieren") ?></neo-button-neo-animate>
            </div>
        </neo-setting-neo-animate>
        <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_start([]); ?>
            #neo-support--buttons { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
            @media (max-width: 767px) { #neo-support--buttons { align-items: flex-start; } }
        <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_end(); ?>
    <?php };
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoSettings\interface_add_neo_setting_20260326')("neo-support--support", $settings_render_callback, show_to_editor: true);
});

\NeoAnimate\NeoGlobal\add_action_hook("neo_init:12", function () {
    $settings_render_callback = function () {
        ?><neo-setting-neo-animate>
            <div slot="left">
                <h3><?php \NeoAnimate\NeoGlobal\echo_neo__("Privacy Policy", "Datenschutzerklärung") ?></h3>
                <p id="neo-support--privacy-text">
                    <?php \NeoAnimate\NeoGlobal\echo_neo__(
                        "This plugin may contact external services. When neoAI is used, images, prompts, and page context are sent through neoAI/OpenAI or directly to the selected AI provider. Do not send personal or confidential data. See the <a href=\"https://" . \NeoAnimate\NeoGlobal\option__neo_wp_com() . \NeoAnimate\NeoGlobal\neo__("", "/de") . "/privacy-policy-plugins/?ref=neo-support\" target=\"_blank\">full privacy policy</a>.",
                        "Dieses Plugin kann externe Dienste kontaktieren. Bei Nutzung von neoAI werden Bilder, Prompts und Seitenkontext über neoAI/OpenAI oder direkt an den gewählten KI-Anbieter gesendet. Übermitteln Sie keine personenbezogenen oder vertraulichen Daten. Details finden Sie in der <a href=\"https://" . \NeoAnimate\NeoGlobal\option__neo_wp_com() . \NeoAnimate\NeoGlobal\neo__("", "/de") . "/privacy-policy-plugins/?ref=neo-support\" target=\"_blank\">vollständigen Datenschutzerklärung</a>.",
                    ) ?>
                </p>
                <?php \NeoAnimate\NeoGlobal\backend_page_style_tag_start([]); ?>#neo-support--privacy-text a { text-decoration: underline; }<?php \NeoAnimate\NeoGlobal\backend_page_style_tag_end(); ?>
            </div>
        </neo-setting-neo-animate>
        <?php
    };
    \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoSettings\interface_add_neo_setting_20260326')("neo-support--privacy-policy", $settings_render_callback, show_to_editor: true);
});
