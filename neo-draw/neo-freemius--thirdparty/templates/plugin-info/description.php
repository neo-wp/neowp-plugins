<?php /* phpcs:ignoreFile */
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
	 * @since       1.0.6
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * @var array $VARS
	 *
	 * @var FS_Plugin $plugin
	 */
	$plugin = $VARS['plugin'];

	if ( ! empty( $plugin->info->selling_point_0 ) ||
	     ! empty( $plugin->info->selling_point_1 ) ||
	     ! empty( $plugin->info->selling_point_2 )
	) : ?>
		<div class="fs-selling-points">
			<ul>
				<?php /* phpcs:ignoreFile */ for ( $i = 0; $i < 3; $i ++ ) : ?>
					<?php /* phpcs:ignoreFile */ if ( ! empty( $plugin->info->{'selling_point_' . $i} ) ) : ?>
						<li><i class="dashicons dashicons-yes"></i>

							<h3><?php /* phpcs:ignoreFile */ echo esc_html( $plugin->info->{'selling_point_' . $i} ) ?></h3></li>
					<?php /* phpcs:ignoreFile */ endif ?>
				<?php /* phpcs:ignoreFile */ endfor ?>
			</ul>
		</div>
	<?php /* phpcs:ignoreFile */ endif ?>
	<div>
		<?php /* phpcs:ignoreFile */
			echo wp_kses( $plugin->info->description, array(
				'a'          => array( 'href' => array(), 'title' => array(), 'target' => array() ),
				'b'          => array(),
				'i'          => array(),
				'p'          => array(),
				'blockquote' => array(),
				'h2'         => array(),
				'h3'         => array(),
				'ul'         => array(),
				'ol'         => array(),
				'li'         => array()
			) );
		?>
	</div>
<?php /* phpcs:ignoreFile */ if ( ! empty( $plugin->info->screenshots ) ) : ?>
	<?php /* phpcs:ignoreFile */ $screenshots = $plugin->info->screenshots ?>
	<div class="fs-screenshots clearfix">
		<h3><?php /* phpcs:ignoreFile */ fs_esc_html_echo_inline( 'Screenshots', 'screenshots', $plugin->slug ) ?></h3>
		<ul>
			<?php /* phpcs:ignoreFile */ $i = 0;
				foreach ( $screenshots as $s => $url ) : ?>
					<li class="<?php /* phpcs:ignoreFile */ echo ( 0 === $i % 2 ) ? 'odd' : 'even' ?>">
						<style>
							#section-description .fs-screenshots <?php /* phpcs:ignoreFile */ echo ".fs-screenshot-{$i}" ?>
							{
								background-image: url('<?php /* phpcs:ignoreFile */ echo $url ?>');
							}
						</style>
						<a href="<?php /* phpcs:ignoreFile */ echo $url ?>"
						   title="<?php /* phpcs:ignoreFile */ echo esc_attr( sprintf( fs_text_inline( 'Click to view full-size screenshot %d', 'view-full-size-x', $plugin->slug ), $i ) ) ?>"
						   class="fs-screenshot-<?php /* phpcs:ignoreFile */ echo $i ?>"></a>
					</li>
					<?php /* phpcs:ignoreFile */ $i ++; endforeach ?>
		</ul>
	</div>
<?php /* phpcs:ignoreFile */ endif ?>