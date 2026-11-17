<?php /* phpcs:ignoreFile */
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.1.4
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 */
	$fs   = freemius( $VARS['id'] );

    $size = isset( $VARS['size'] ) ? $VARS['size'] : 80;
?>
<div class="fs-plugin-icon">
	<img src="<?php /* phpcs:ignoreFile */ echo $fs->get_local_icon_url() ?>" width="<?php /* phpcs:ignoreFile */ echo $size ?>" height="<?php /* phpcs:ignoreFile */ echo $size ?>" />
</div>