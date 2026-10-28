<?php
namespace NeoOptimize\NeoConsoleGreeting; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoOptimize\NeoGlobal\add_action_hook("init", function() {
    if (!str_starts_with(get_locale(), "de")) { return; }
    if (!\NeoOptimize\NeoGlobal\current_user_can__neo_console_greeting()) { return; }
    if (\NeoOptimize\NeoGlobal\option__neo_console_greeting__hide()) { return; }

    \NeoOptimize\NeoGlobal\enqueue_js_variable_backend_and_frontend("neoConsoleGreeting", [
        "message" => "🙋‍♂️ Hey! Wenn du diese Nachricht liest, dann bist du als Admin angemeldet und verfügst über besondere technische Fähigkeiten. Bist du begeistert von neoPlugins und möchtest mitwirken? Wir laden dich ein uns kennenzulernen und freuen uns auf deine Nachricht! 💌 hallo@neo-wp.com Um diese Meldung für immer auszublenden, klicke hier: ",
        "hideUrl" => \NeoOptimize\NeoGlobal\get_backend_page_url("neo-console-greeting--hide"),
    ]);
    \NeoOptimize\NeoGlobal\add_action_hook("wp_enqueue_scripts", "admin_enqueue_scripts", function () {
        \NeoOptimize\NeoGlobal\enqueue_js("neo-console-greeting.js");
    });
});

\NeoOptimize\NeoGlobal\register_backend_page("neo-console-greeting--hide", fn () => \NeoOptimize\NeoGlobal\current_user_can__neo_console_greeting(), function () {
    \NeoOptimize\NeoGlobal\option__neo_console_greeting__hide(true);
    ?>
    <html style="height:100%;">
        <head><title><?php \NeoOptimize\NeoGlobal\echo_neo__("Success!", "Erfolg!") ?></title></head>
        <body style="font-family: sans-serif;">
            <?php \NeoOptimize\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
                import Swal from "<?php echo esc_url(\NeoOptimize\NeoGlobal\plugin_url()) ?>/_global-sweetalert2.js";
                import { reloadPage } from "<?php echo esc_url(\NeoOptimize\NeoGlobal\plugin_url()) ?>/_global-reload-page.js";
                await Swal.fire({
                    icon: "success",
                    title: "<?php \NeoOptimize\NeoGlobal\echo_neo__("Success!", "Erfolg!") ?>",
                    text: "<?php \NeoOptimize\NeoGlobal\echo_neo__("The message won't be shown again.", "Die Nachricht wird nicht mehr angezeigt.") ?>",
                    confirmButtonText: '<?php \NeoOptimize\NeoGlobal\echo_neo__("OK", "OK") ?>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                });
                reloadPage("<?php echo esc_url(admin_url()) ?>");
            <?php \NeoOptimize\NeoGlobal\backend_page_script_tag_end(); ?>
        </body>
    </html><?php
});
