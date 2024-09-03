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
get_template_hierarchy( 'foo' );

// ============= //
// Passing usage //
// ============= //

// 6.0.0
$locale = new WP_Locale();
$locale->get_list_item_separator();

// 5.9.0
$debug = new WP_Debug_Data();
$debug->get_mysql_var( 'foo' );

// 5.8.0
get_adjacent_image_link();
