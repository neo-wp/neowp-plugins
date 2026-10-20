<?php
namespace NeoAnimate\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\register_backend_page("neo-draw--icon-library", fn () => \NeoAnimate\NeoGlobal\current_user_can__neo_draw(), function () {
    ?>
    <html>
    <head>
        <title>Add Library</title>
        <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
            import { domLoaded } from "<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global--observer.js";

            const channel = new BroadcastChannel("neo-draw--iframe");

            channel.addEventListener("message", (event) => {
                if (event.data.action === "addLibraryConfirm") {
                    window.close();
                }
            });

            domLoaded(() => {
                channel.postMessage({
                    action: "addLibrary",
                    hash: location.hash
                });
            });
        <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?>
    </head>

    <body>
        Adding the library...
    </body>

    </html>
<?php });
