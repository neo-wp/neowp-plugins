<?php /* phpcs:ignoreFile */
    /**
     * @package     Freemius
     * @copyright   Copyright (c) 2022, Freemius, Inc.
     * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
     * @since       2.5.1
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * @var array $VARS
     * @var array $permission {
     * @type string $id
     * @type bool   $default
     * @type string $icon-class
     * @type bool   $optional
     * @type string $label
     * @type string $tooltip
     * @type string $desc
     * }
     */
    $permission = $VARS;

    $is_permission_on = ( ! isset( $permission['default'] ) || true === $permission['default'] );
?>
<li id="fs_permission_<?php /* phpcs:ignoreFile */ echo esc_attr( $permission['id'] ) ?>" data-permission-id="<?php /* phpcs:ignoreFile */ echo esc_attr( $permission['id'] ) ?>"
    class="fs-permission fs-<?php /* phpcs:ignoreFile */ echo esc_attr( $permission['id'] ); ?><?php /* phpcs:ignoreFile */ echo ( ! $is_permission_on ) ? ' fs-disabled' : ''; ?>">
    <i class="<?php /* phpcs:ignoreFile */ echo esc_attr( $permission['icon-class'] ); ?>"></i>
    <?php /* phpcs:ignoreFile */ if ( isset( $permission['optional'] ) && true === $permission['optional'] ) : ?>
        <div class="fs-switch fs-small fs-round fs-<?php /* phpcs:ignoreFile */ echo $is_permission_on ? 'on' : 'off' ?>">
            <div class="fs-toggle"></div>
        </div>
    <?php /* phpcs:ignoreFile */ endif ?>

    <div class="fs-permission-description">
        <span<?php /* phpcs:ignoreFile */ if ( ! empty( $permission['tooltip'] ) ) : ?> class="fs-tooltip-trigger"<?php /* phpcs:ignoreFile */ endif ?>><?php /* phpcs:ignoreFile */ echo esc_html( $permission['label'] ); ?><?php /* phpcs:ignoreFile */ if ( ! empty( $permission['tooltip'] ) ) : ?><i class="dashicons dashicons-editor-help"><span class="fs-tooltip" style="width: 200px"><?php /* phpcs:ignoreFile */ echo esc_html( $permission['tooltip'] ) ?></span></i><?php /* phpcs:ignoreFile */ endif ?></span>

        <p><?php /* phpcs:ignoreFile */ echo esc_html( $permission['desc'] ); ?></p>
    </div>
</li>