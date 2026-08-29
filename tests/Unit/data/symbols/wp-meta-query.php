<?php

/*
 * The class docblock, and the docblock and signature of WP_Meta_Query::__construct(), copied
 * verbatim from WordPress core: wp-includes/class-wp-meta-query.php. The method body and the
 * rest of the class are omitted as they're not used.
 */

/**
 * Core class used to implement meta queries for the Meta API.
 *
 * Used for generating SQL clauses that filter a primary query according to metadata keys and values.
 *
 * WP_Meta_Query is a helper that allows primary query classes, such as WP_Query and WP_User_Query,
 *
 * to filter their results by object metadata, by generating `JOIN` and `WHERE` subclauses to be attached
 * to the primary SQL query string.
 *
 * @since 3.2.0
 */
#[AllowDynamicProperties]
class WP_Meta_Query {

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 * @since 4.2.0 Introduced support for naming query clauses by associative array keys.
	 * @since 5.1.0 Introduced `$compare_key` clause parameter, which enables LIKE key matches.
	 * @since 5.3.0 Increased the number of operators available to `$compare_key`. Introduced `$type_key`,
	 *              which enables the `$key` to be cast to a new data type for comparisons.
	 *
	 * @param array $meta_query {
	 *     Array of meta query clauses. When first-order clauses or sub-clauses use strings as
	 *     their array keys, they may be referenced in the 'orderby' parameter of the parent query.
	 *
	 *     @type string $relation Optional. The MySQL keyword used to join the clauses of the query.
	 *                            Accepts 'AND' or 'OR'. Default 'AND'.
	 *     @type array  ...$0 {
	 *         Optional. An array of first-order clause parameters, or another fully-formed meta query.
	 *
	 *         @type string|string[] $key         Meta key or keys to filter by.
	 *         @type string          $compare_key MySQL operator used for comparing the $key. Accepts:
	 *                                            - '='
	 *                                            - '!='
	 *                                            - 'LIKE'
	 *                                            - 'NOT LIKE'
	 *                                            - 'IN'
	 *                                            - 'NOT IN'
	 *                                            - 'REGEXP'
	 *                                            - 'NOT REGEXP'
	 *                                            - 'RLIKE'
	 *                                            - 'EXISTS' (alias of '=')
	 *                                            - 'NOT EXISTS' (alias of '!=')
	 *                                            Default is 'IN' when `$key` is an array, '=' otherwise.
	 *         @type string          $type_key    MySQL data type that the meta_key column will be CAST to for
	 *                                            comparisons. Accepts 'BINARY' for case-sensitive regular expression
	 *                                            comparisons. Default is ''.
	 *         @type string|string[] $value       Meta value or values to filter by.
	 *         @type string          $compare     MySQL operator used for comparing the $value. Accepts:
	 *                                            - '='
	 *                                            - '!='
	 *                                            - '>'
	 *                                            - '>='
	 *                                            - '<'
	 *                                            - '<='
	 *                                            - 'LIKE'
	 *                                            - 'NOT LIKE'
	 *                                            - 'IN'
	 *                                            - 'NOT IN'
	 *                                            - 'BETWEEN'
	 *                                            - 'NOT BETWEEN'
	 *                                            - 'REGEXP'
	 *                                            - 'NOT REGEXP'
	 *                                            - 'RLIKE'
	 *                                            - 'EXISTS'
	 *                                            - 'NOT EXISTS'
	 *                                            Default is 'IN' when `$value` is an array, '=' otherwise.
	 *         @type string          $type        MySQL data type that the meta_value column will be CAST to for
	 *                                            comparisons. Accepts:
	 *                                            - 'NUMERIC'
	 *                                            - 'BINARY'
	 *                                            - 'CHAR'
	 *                                            - 'DATE'
	 *                                            - 'DATETIME'
	 *                                            - 'DECIMAL'
	 *                                            - 'SIGNED'
	 *                                            - 'TIME'
	 *                                            - 'UNSIGNED'
	 *                                            Default is 'CHAR'.
	 *     }
	 * }
	 */
	public function __construct( $meta_query = array() ) {}
}
