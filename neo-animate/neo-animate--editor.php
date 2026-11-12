<?php
namespace NeoAnimate\NeoAnimate; if (!defined("ABSPATH")) { die("neoWP direct access not allowed"); }

\NeoAnimate\NeoGlobal\register_backend_page("neo-animate--editor", fn () => \NeoAnimate\NeoGlobal\current_user_can__neo_animate(), function () {
    [$neo_log_js_url, $neo_log_interface_ok] = \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoLog\interface_get_neo_log_js_url_20260621')();
    ?>
    <!DOCTYPE html>
    <html>
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>neoAnimate</title>

            <?php \NeoAnimate\NeoGlobal\backend_page_stylesheet_link_tag(["rel" => "stylesheet", "href" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/neo-animate--editor.css"), ENT_QUOTES)]); ?><!-- Style tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet */ ?>

            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start([]); ?>
                <?php echo \NeoAnimate\NeoGlobal\get_code_for_js_variables("backend") /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>; /* Output is properly escaped in get_code_for_js_variables() for security */
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?>
            <?php if ($neo_log_interface_ok) { ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url($neo_log_js_url), ENT_QUOTES), "type" => "module"]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><?php } ?> <!-- Script tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
        </head>
        <body>
            <animation-window>
                <image-container>

                </image-container>
                <settings-area-container>
                    <hr />
                    <settings-area>
                        <div class="button timing select-button" tabindex="0">
                            <img
                                src="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/img/neo-animate--timing-icon.svg"
                                alt="Timing"
                            />
                            <neo-select-neo-animate id="timing" selected-display="empty">
                                <optgroup label="Timing"><?php \NeoAnimate\NeoGlobal\echo_neo__("Frame Timing", "Einzelbild-Zeitablauf"); ?>
                                    <option value="sync"><?php \NeoAnimate\NeoGlobal\echo_neo__("Sync", "Synchron"); ?></option>
                                    <option value="delayed"><?php \NeoAnimate\NeoGlobal\echo_neo__("Delayed", "Verzögert"); ?></option>
                                    <option value="overlapping"><?php \NeoAnimate\NeoGlobal\echo_neo__("Overlapping", "Überlappend"); ?></option>
                                    <option value="one-by-one" show-pro-crown-if-not-pro><?php \NeoAnimate\NeoGlobal\echo_neo__("One-by-one", "Nacheinander"); ?></option>
                                    <option value="instant"><?php \NeoAnimate\NeoGlobal\echo_neo__("Instant", "Sofort"); ?></option>
                                </optgroup>
                            </neo-select-neo-animate>
                        </div>
                        <label class="duration">
                            <input
                                type="number"
                                min="0.1"
                                value="1.0"
                                step="0.1"
                            />
                            <span class="seconds">seconds</span>
                        </label>
                    </settings-area>
                </settings-area-container>
                <timeline-area>
                    <timeline-controls>
                        <button class="button play" onclick="window.neoAnimateTogglePlayStop();">
                            <img src="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/img/neo-animate--play-icon.svg" alt="Play" />
                        </button>
                        <div class="button settings select-button" tabindex="0">
                            <img src="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/img/neo-animate--trigger-icon.svg" alt="Trigger" />
                            <neo-select-neo-animate id="trigger" selected-display="empty">
                                <optgroup label="Trigger"><?php \NeoAnimate\NeoGlobal\echo_neo__("Trigger", "Auslöser"); ?>
                                    <option value="start-when-visible" data-icon-url="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/eye.svg"><?php \NeoAnimate\NeoGlobal\echo_neo__("Start when visible", "Starten sobald sichtbar"); ?></option>
                                    <option value="start-once-when-visible" data-icon-url="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/badge-check.svg"><?php \NeoAnimate\NeoGlobal\echo_neo__("Start once when visible", "Einmalig starten sobald sichtbar"); ?></option>
                                    <option value="scroll-position-y" data-icon-url="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/scroll-text.svg"><?php \NeoAnimate\NeoGlobal\echo_neo__("Sync to scroll position", "Synchron zur Scrollposition"); ?></option>
                                    <option value="mouse-position-y" data-icon-url="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/mouse-pointer-2.svg" show-pro-crown-if-not-pro><?php \NeoAnimate\NeoGlobal\echo_neo__("Sync to mouse position", "Synchron zur Mausposition"); ?></option>
                                    <option value="repeating-infinitely" data-icon-url="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/repeat.svg"><?php \NeoAnimate\NeoGlobal\echo_neo__("Repeating infinitely", "Unendlich wiederholen"); ?></option>
                                    <option value="on-image-hover" data-icon-url="<?php echo esc_url(\NeoAnimate\NeoGlobal\plugin_url()) ?>/_global-lucide-icons-thirdparty/mouse-pointer-click.svg"><?php \NeoAnimate\NeoGlobal\echo_neo__("On image hover", "Maus über Bild"); ?></option>
                                </optgroup>
                            </neo-select-neo-animate>
                        </div>
                    </timeline-controls>
                    <timeline-scroll-container>
                        <timeline-container>

                        </timeline-container>
                    </timeline-scroll-container>
                </timeline-area>
            </animation-window>
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/_global-web-component-importer.js"), ENT_QUOTES), "type" => "module"]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php \NeoAnimate\NeoGlobal\backend_page_script_tag_start(["src" => \wp_specialchars_decode(esc_url(\NeoAnimate\NeoGlobal\plugin_url() . "/neo-animate--editor.js"), ENT_QUOTES), "type" => "module"]); ?><?php \NeoAnimate\NeoGlobal\backend_page_script_tag_end(); ?><!-- Script tag instead of enqueue function because it's required for the iframe. --> <?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript */ ?>
            <?php \NeoAnimate\NeoGlobal\call_interface_func_implemented('\NeoAnimate\NeoDemoScreenshot\interface_render_neo_animate_editor_screenshot_setup_20260611')();?>
        </body>
    </html>
<?php });
