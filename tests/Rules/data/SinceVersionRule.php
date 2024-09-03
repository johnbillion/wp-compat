<?php

// ============= //
// Failing usage //
// ============= //

// 6.0.3
$query = new WP_Date_Query( [] );
$query->sanitize_relation( 'AND' );

// 6.1.0
$cache = new WP_Object_Cache();
$cache->flush_group( 'foo' );

// 6.1.0
if ( class_exists( 'WP_Object_Cache' ) ) {
	$cache = new WP_Object_Cache();
	$cache->flush_group( 'foo' );
}

// 6.1.0
get_template_hierarchy( 'foo' );

// ============= //
// Passing usage //
// ============= //

// 6.1.0
if ( function_exists( 'get_template_hierarchy' ) ) {
	get_template_hierarchy( 'foo' );
}

// 6.0.3
$query = new WP_Date_Query( [] );
if ( method_exists( $query, 'sanitize_relation' ) ) {
	$query->sanitize_relation( 'AND' );
}

// 6.0.3
if ( method_exists( 'WP_Date_Query', 'sanitize_relation' ) ) {
	$query = new WP_Date_Query( [] );
	$query->sanitize_relation( 'AND' );
}

// 6.0.3
$query = new WP_Date_Query( [] );
if ( method_exists( $query, 'sanitize_relation' ) ) {
	do_something_unrelated();

	for ( $i = 0; $i < 10; $i++ ) {
		$query->sanitize_relation( 'AND' );
	}
}

// 6.0.0
$locale = new WP_Locale();
$locale->get_list_item_separator();

// 5.9.0
$debug = new WP_Debug_Data();
$debug->get_mysql_var( 'foo' );

// 5.8.0
get_adjacent_image_link();
