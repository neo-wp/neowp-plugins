<?php /* phpcs:ignoreFile */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    /**
     * @var array $VARS
     * @var Freemius $fs
     */
    $fs = freemius( $VARS['id'] );

    $slug = $fs->get_slug();

    $sites               = $VARS['sites'];
    $require_license_key = $VARS['require_license_key'];

    $show_delegation_option     = $fs->apply_filters( 'show_delegation_option', true );
    $enable_per_site_activation = $fs->apply_filters( 'enable_per_site_activation', true );
?>
<?php /* phpcs:ignoreFile */ $separator      = '<td>|</td>' ?>
<div class="fs-multisite-options-container fs-apply-on-all-sites"<?php /* phpcs:ignoreFile */ if ( ! $enable_per_site_activation )
    echo ' style="display: none;"' ?>>
    <table class="fs-all-sites-options">
        <tbody>
        <tr>
            <td width="600">
                <label>
                    <?php /* phpcs:ignoreFile */
                        if ( ! $fs->is_network_upgrade_mode() ) {
                            $apply_checkbox_label = $require_license_key ?
                                fs_text_inline( 'Activate license on all sites in the network.', 'activate-license-on-all-sites-in-the-network', $slug ) :
                                fs_text_inline( 'Apply on all sites in the network.', 'apply-on-all-sites-in-the-network', $slug );
                        } else {
                            $apply_checkbox_label = $require_license_key ?
                                fs_text_inline( 'Activate license on all pending sites.', 'activate-license-on-pending-sites-in-the-network', $slug ) :
                                fs_text_inline( 'Apply on all pending sites.', 'apply-on-pending-sites-in-the-network', $slug );

                        }
                    ?>
                    <input class="fs-apply-on-all-sites-checkbox" type="checkbox" value="true" checked><span><?php /* phpcs:ignoreFile */ echo esc_html( $apply_checkbox_label ) ?></span>
                </label>
            </td>
            <?php /* phpcs:ignoreFile */ if ( ! $require_license_key ) : ?>
                <td><a class="action action-allow" data-action-type="allow" href="#"><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'allow', 'allow', $slug ) ?></a></td>
                <?php /* phpcs:ignoreFile */ echo $separator ?>
                <?php /* phpcs:ignoreFile */ if ( $show_delegation_option ) : ?>
                <td><a class="action action-delegate" data-action-type="delegate" href="#"><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'delegate', 'delegate', $slug ) ?></a></td>
                <?php /* phpcs:ignoreFile */ endif ?>
                <?php /* phpcs:ignoreFile */ if ( $fs->is_enable_anonymous() ) : ?>
                    <?php /* phpcs:ignoreFile */ echo $separator ?>
                    <td><a class="action action-skip" data-action-type="skip" href="#"><?php /* phpcs:ignoreFile */ echo strtolower( fs_esc_html_inline( 'skip', 'skip', $slug ) ) ?></a></td>
                <?php /* phpcs:ignoreFile */ endif ?>
            <?php /* phpcs:ignoreFile */ endif ?>
        </tr>
        </tbody>
    </table>
    <div class="fs-sites-list-container">
        <table cellspacing="0">
            <tbody>
            <?php /* phpcs:ignoreFile */ $site_props = array('uid', 'url', 'title', 'language') ?>
            <?php /* phpcs:ignoreFile */ foreach ( $sites as $site ) : ?>
                <tr<?php /* phpcs:ignoreFile */ if ( ! empty( $site['license_id'] ) ) {
                    echo ' data-license-id="' . esc_attr( $site['license_id'] ) . '"';
                } ?>>
                    <?php /* phpcs:ignoreFile */ if ( $require_license_key ) : ?>
                        <td><input type="checkbox" value="true" /></td>
                    <?php /* phpcs:ignoreFile */ endif ?>
                    <td class="blog-id"><span><?php /* phpcs:ignoreFile */ echo esc_html( $site['blog_id'] ) ?></span>.</td>
                    <td width="600"><span><?php /* phpcs:ignoreFile */
                        $url = str_replace( 'http://', '', str_replace( 'https://', '', $site['url'] ) );
                        echo esc_html( $url );
                        ?></span>
                        <?php /* phpcs:ignoreFile */ foreach ($site_props as $prop) : ?>
                            <input class="<?php /* phpcs:ignoreFile */ echo esc_attr( $prop ) ?>" type="hidden" value="<?php /* phpcs:ignoreFile */ echo esc_attr($site[$prop]) ?>" />
                        <?php /* phpcs:ignoreFile */ endforeach ?>
                    </td>
                    <?php /* phpcs:ignoreFile */ if ( ! $require_license_key ) : ?>
                        <td><a class="action action-allow selected" data-action-type="allow" href="#"><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'allow', 'allow', $slug ) ?></a></td>
                        <?php /* phpcs:ignoreFile */ echo $separator ?>
                        <?php /* phpcs:ignoreFile */ if ( $show_delegation_option ) : ?>
                        <td><a class="action action-delegate" data-action-type="delegate" href="#"><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'delegate', 'delegate', $slug ) ?></a></td>
                        <?php /* phpcs:ignoreFile */ endif ?>
                        <?php /* phpcs:ignoreFile */ if ( $fs->is_enable_anonymous() ) : ?>
                            <?php /* phpcs:ignoreFile */ echo $separator ?>
                            <td><a class="action action-skip" data-action-type="skip" href="#"><?php /* phpcs:ignoreFile */ echo strtolower( fs_esc_html_inline( 'skip', 'skip', $slug ) ) ?></a></td>
                        <?php /* phpcs:ignoreFile */ endif ?>
                    <?php /* phpcs:ignoreFile */ endif ?>
                </tr>
            <?php /* phpcs:ignoreFile */ endforeach ?>
            </tbody>
        </table>
    </div>
</div>
