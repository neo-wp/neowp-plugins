<?php /* phpcs:ignoreFile */
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.1.1
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 */
	$sections = $VARS['sections'];
?>
<table>
	<?php /* phpcs:ignoreFile */
	foreach ( $sections as $section_id => $section ) {
		?>
		<thead>
			<tr><th colspan="2" style="text-align: left; background: #333; color: #fff; padding: 5px;"><?php /* phpcs:ignoreFile */ echo esc_html($section['title']) ?></th></tr>
		</thead>
		<tbody>
		<?php /* phpcs:ignoreFile */
		foreach ( $section['rows'] as $row_id => $row ) {
			$col_count = count( $row );
			?>
			<tr>
				<?php /* phpcs:ignoreFile */
				if ( 1 === $col_count ) { ?>
					<td style="vertical-align: top;" colspan="2"><?php /* phpcs:ignoreFile */ echo $row[0] ?></td>
					<?php /* phpcs:ignoreFile */
				} else { ?>
					<td style="vertical-align: top;"><b><?php /* phpcs:ignoreFile */ echo esc_html($row[0]) ?>:</b></td>
					<td><?php /* phpcs:ignoreFile */ echo $row[1]; ?></td>
					<?php /* phpcs:ignoreFile */
				}
				?>
			</tr>
			<?php /* phpcs:ignoreFile */
		}
		?>
		</tbody>
		<?php /* phpcs:ignoreFile */
	}
	?>
</table>