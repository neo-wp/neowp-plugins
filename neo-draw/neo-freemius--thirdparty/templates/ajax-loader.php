<?php /* phpcs:ignoreFile */
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
?>
<div class="fs-ajax-loader" style="display: none"><?php /* phpcs:ignoreFile */ for ( $i = 1; $i <= 8; $i ++ ) : ?><div class="fs-ajax-loader-bar fs-ajax-loader-bar-<?php /* phpcs:ignoreFile */ echo $i ?>"></div><?php /* phpcs:ignoreFile */ endfor ?></div>
