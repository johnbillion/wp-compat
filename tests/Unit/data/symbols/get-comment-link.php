<?php

/*
 * The docblock and signature of get_comment_link(), copied verbatim from WordPress core:
 * wp-includes/comment-template.php. The function body is omitted as it's not used.
 */

/**
 * Retrieves the link to a given comment.
 *
 * @since 1.5.0
 * @since 4.4.0 Added the ability for `$comment` to also accept a WP_Comment object. Added `$cpage` argument.
 *
 * @see get_page_of_comment()
 *
 * @global WP_Rewrite $wp_rewrite      WordPress rewrite component.
 * @global bool       $in_comment_loop
 *
 * @param WP_Comment|int|null $comment Optional. Comment to retrieve. Default current comment.
 * @param array               $args {
 *     An array of optional arguments to override the defaults.
 *
 *     @type string     $type      Passed to get_page_of_comment().
 *     @type int        $page      Current page of comments, for calculating comment pagination.
 *     @type int        $per_page  Per-page value for comment pagination.
 *     @type int        $max_depth Passed to get_page_of_comment().
 *     @type int|string $cpage     Value to use for the comment's "comment-page" or "cpage" value.
 *                                 If provided, this value overrides any value calculated from `$page`
 *                                 and `$per_page`.
 * }
 * @return string The permalink to the given comment.
 */
function get_comment_link( $comment = null, $args = array() ) {}
