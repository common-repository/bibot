<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die( 'Direct access not allowed!' );
}

function bib_delete($array) {
	foreach ($array as $one) {
		delete_option("bib_{$one}");
	}
}

bib_delete(array("site_key", "secret_key", "login_check_disable"));
