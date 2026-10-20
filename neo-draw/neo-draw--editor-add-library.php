<?php
namespace NeoDraw\NeoDraw; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoDraw\NeoGlobal\register_backend_page("neo-draw--icon-library", fn () => \NeoDraw\NeoGlobal\current_user_can__neo_draw(), function () {
    ?>
    <html>
    <head>
        <title>Add Library</title>
        <?php \NeoDraw\NeoGlobal\backend_page_script_tag_start(["type" => "module"]); ?>
            import { domLoaded } from "<?php echo esc_url(\NeoDraw\NeoGlobal\plugin_url()) ?>/_global--observer.js";

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
        <?php \NeoDraw\NeoGlobal\backend_page_script_tag_end(); ?>
    </head>

    <body>
        Adding the library...
    </body>

    </html>
<?php });
