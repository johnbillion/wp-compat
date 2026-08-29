<?php

/*
 * The docblock and signature of add_rewrite_rule(), copied verbatim from WordPress core:
 * wp-includes/rewrite.php. The function body is omitted as it's not used.
 */

/**
 * Adds a rewrite rule that transforms a URL structure to a set of query vars.
 *
 * Any value in the $after parameter that isn't 'bottom' will result in the rule
 * being placed at the top of the rewrite rules.
 *
 * @since 2.1.0
 * @since 4.4.0 Array support was added to the `$query` parameter.
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param string       $regex Regular expression to match request against.
 * @param string|array $query The corresponding query vars for this rewrite rule.
 * @param string       $after Optional. Priority of the new rule. Accepts 'top'
 *                            or 'bottom'. Default 'bottom'.
 */
function add_rewrite_rule( $regex, $query, $after = 'bottom' ) {}
