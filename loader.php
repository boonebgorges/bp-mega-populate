<?php
/*
Plugin name: BP Mega Populate
Description: Creates tons of BP dummy data for performance testing
Author: Boone B Gorges
License: GPLv2
*/

/**
 * Only load if BP is present
 */
function bpmp_load() {
	include( dirname( __FILE__ ) . '/bp-mega-populate.php' );
}
add_action( 'bp_include', 'bpmp_load' );

?>