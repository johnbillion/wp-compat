<?php

// ============= //
// Failing usage //
// ============= //

// Method introduced in a point release of the tested major (6.0.3)
$query = new WP_Date_Query( [] );
$query->sanitize_relation( 'AND' );

// Method introduced in a subsequent major (6.1.0)
$cache = new WP_Object_Cache();
$cache->flush_group( 'foo' );

// Method introduced in a subsequent major (6.1.0) surrounded by an uneffective conditional
if ( class_exists( 'WP_Object_Cache' ) ) {
	$cache = new WP_Object_Cache();
	$cache->flush_group( 'foo' );
}

// Function introduced in a subsequent major (6.1.0)
get_template_hierarchy( 'foo' );

// Inherited method introduced in a subsequent major (6.1.0)
class My_Date_Query extends WP_Date_Query {}
class Best_Date_Query extends My_Date_Query {}
$query = new Best_Date_Query( [] );
$query->sanitize_relation( 'AND' );

// Static method introduced in a subsequent major (6.5.0)
$registry = WP_Block_Bindings_Registry::get_instance();

// Second call to a function introduced in a subsequent major (6.1.0) after an earlier guard that does not apply to the second call
if ( function_exists( 'get_template_hierarchy' ) ) {
	get_template_hierarchy( 'foo' );
}
get_template_hierarchy( 'foo' );


// ============= //
// Passing usage //
// ============= //

// Function introduced in a subsequent major (6.1.0) correctly guarded
if ( function_exists( 'get_template_hierarchy' ) ) {
	get_template_hierarchy( 'foo' );
}

// Method introduced in a point release of the tested major (6.0.3) correctly guarded
$query = new WP_Date_Query( [] );
if ( method_exists( $query, 'sanitize_relation' ) ) {
	$query->sanitize_relation( 'AND' );
}

// Method introduced in a point release of the tested major (6.0.3) correctly guarded
if ( method_exists( 'WP_Date_Query', 'sanitize_relation' ) ) {
	$query = new WP_Date_Query( [] );
	$query->sanitize_relation( 'AND' );
}

// Method introduced in a point release of the tested major (6.0.3) with additional code after the guard
$query = new WP_Date_Query( [] );
if ( method_exists( $query, 'sanitize_relation' ) ) {
	do_something_unrelated();

	for ( $i = 0; $i < 10; $i++ ) {
		$query->sanitize_relation( 'AND' );
	}
}

// Method introduced in the tested version (6.0.0)
$locale = new WP_Locale();
$locale->get_list_item_separator();

// Method introduced in a prior major (5.9.0)
$debug = new WP_Debug_Data();
$debug->get_mysql_var( 'foo' );

// Function introduced in a prior major (5.8.0)
get_adjacent_image_link();

// Static method introduced in a prior major (3.7.0)
$should_upgrade = Core_Upgrader::should_update_to_version( 'foo' );

function boop(): void {
	if ( ! function_exists( 'get_adjacent_image_link' ) ) {
		return;
	}

	// Function introduced in a prior major (5.8.0) but guarded by a return
	get_adjacent_image_link();
}
boop();
