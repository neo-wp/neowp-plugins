<?php
namespace NeoMotion\NeoConsoleGreeting; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoMotion\NeoGlobal\add_action_hook("init", function() {
    if (!str_starts_with(get_locale(), "de")) { return; }
    if (!\NeoMotion\NeoGlobal\current_user_can__neo_console_greeting()) { return; }
    if (\NeoMotion\NeoGlobal\option__neo_console_greeting__hide()) { return; }

    \NeoMotion\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoConsoleGreeting", [
        "message" => "🙋‍♂️ Hey! Wenn du diese Nachricht liest, dann bist du als Admin angemeldet und verfügst über besondere technische Fähigkeiten. Bist du begeistert von neoPlugins und möchtest mitwirken? Wir laden dich ein uns kennenzulernen und freuen uns auf deine Nachricht! 💌 hallo@neo-wp.com Um diese Meldung für immer auszublenden, klicke hier: ",
        "hideUrl" => \NeoMotion\NeoGlobal\get_backend_page_url("neo-console-greeting--hide"),
    ]);
    \NeoMotion\NeoGlobal\add_action_hook("wp_enqueue_scripts", "admin_enqueue_scripts", function () {
        \NeoMotion\NeoGlobal\enqueue_js("neo-console-greeting.js");
    });
});

\NeoMotion\NeoGlobal\register_backend_page("neo-console-greeting--hide", fn () => \NeoMotion\NeoGlobal\current_user_can__neo_console_greeting(), function () {
    \NeoMotion\NeoGlobal\option__neo_console_greeting__hide(true);
    ?>
    <html style="height:100%;">
        <head><title><?php \NeoMotion\NeoGlobal\echo_neo__("Success!", "Erfolg!") ?></title></head>
        <body style="font-family: sans-serif;">
            <?php \NeoMotion\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
                import Swal from "<?php echo esc_url(\NeoMotion\NeoGlobal\plugin_url()) ?>/_global-sweetalert2.js";
                import { reloadPage } from "<?php echo esc_url(\NeoMotion\NeoGlobal\plugin_url()) ?>/_global-reload-page.js";
                await Swal.fire({
                    icon: "success",
                    title: "<?php \NeoMotion\NeoGlobal\echo_neo__("Success!", "Erfolg!") ?>",
                    text: "<?php \NeoMotion\NeoGlobal\echo_neo__("The message won't be shown again.", "Die Nachricht wird nicht mehr angezeigt.") ?>",
                    confirmButtonText: '<?php \NeoMotion\NeoGlobal\echo_neo__("OK", "OK") ?>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                });
                reloadPage("<?php echo esc_url(admin_url()) ?>");
            <?php \NeoMotion\NeoGlobal\backend_page_script_tag_end(); ?>
        </body>
    </html><?php
});
