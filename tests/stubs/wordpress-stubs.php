<?php
/**
 * Structural stubs for symbols that the rule reflects on.
 *
 * Every other symbol used in the test data is resolved by name from symbols.json
 * or the hooks package. Only symbols whose parents or parameter names get inspected need
 * to be declared here.
 */

class WP_Date_Query {
	public function sanitize_relation( $relation ) {}
}

class WP_Theme_JSON {
	public function sanitize( $input = array(), $valid_block_names = array(), $valid_element_names = array(), $valid_variations = array() ) {}
}

function load_textdomain( $domain, $mofile, $locale = null ) {}
