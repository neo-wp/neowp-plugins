<?php /* phpcs:ignoreFile */
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.1.7.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$log_book = FS_Logger::get_log();
?>
<h1><?php /* phpcs:ignoreFile */ fs_echo_inline( 'Log' ) ?></h1>

<table class="widefat" style="font-size: 11px;">
	<thead>
	<tr>
		<th>#</th>
		<th><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'ID', 'id' ) ?></th>
		<th><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'Type' ) ?></th>
		<th><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'Function' ) ?></th>
		<th><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'Message' ) ?></th>
		<th><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'File' ) ?></th>
		<th><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'Timestamp' ) ?></th>
	</tr>
	</thead>
	<tbody>

	<?php /* phpcs:ignoreFile */ $i = 0;
		foreach ( $log_book as $log ) : ?>
			<?php /* phpcs:ignoreFile */
			/**
			 * @var FS_Logger $logger
			 */
			$logger = $log['logger'];
			?>
			<tr<?php /* phpcs:ignoreFile */ if ( $i % 2 ) {
				echo ' class="alternate"';
			} ?>>
				<td><?php /* phpcs:ignoreFile */ echo $log['cnt'] ?>.</td>
				<td><?php /* phpcs:ignoreFile */ echo $logger->get_id() ?></td>
				<td><?php /* phpcs:ignoreFile */ echo $log['log_type'] ?></td>
				<td><b><code style="color: blue;"><?php /* phpcs:ignoreFile */ echo ( ! empty( $log['class'] ) ? $log['class'] . $log['type'] : '' ) . $log['function'] ?></code></b></td>
				<td>
					<?php /* phpcs:ignoreFile */
						printf(
							'<a href="#" style="color: darkorange !important;" onclick="jQuery(this).parent().find(\'div\').toggle(); return false;"><nobr>%s</nobr></a>',
							esc_html( substr( $log['msg'], 0, 32 ) ) . ( 32 < strlen( $log['msg'] ) ? '...' : '' )
						);
					?>
					<div style="display: none;">
						<b style="color: darkorange;"><?php /* phpcs:ignoreFile */ echo esc_html( $log['msg'] ) ?></b>
					</div>
				</td>
				<td><?php /* phpcs:ignoreFile */
						if ( isset( $log['file'] ) ) {
							echo substr( $log['file'], $logger->get_file() ) . ':' . $log['line'];
						}
					?></td>
				<td><?php /* phpcs:ignoreFile */ echo number_format( 100 * ( $log['timestamp'] - WP_FS__SCRIPT_START_TIME ), 2 ) . ' ' . fs_text_x_inline( 'ms', 'milliseconds' ) ?></td>
			</tr>
			<?php /* phpcs:ignoreFile */ $i ++; endforeach ?>
	</tbody>
</table>