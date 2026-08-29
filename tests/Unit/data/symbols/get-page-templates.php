<?php

/*
 * The docblock and signature of get_page_templates(), copied verbatim from WordPress core:
 * wp-admin/includes/theme.php. The function body is omitted as it's not used.
 */

/**
 * Gets the page templates available in this theme.
 *
 * @since 1.5.0
 * @since 4.7.0 Added the `$post_type` parameter.
 *
 * @param WP_Post|null $post      Optional. The post being edited, provided for context.
 * @param string       $post_type Optional. Post type to get the templates for. Default 'page'.
 * @return string[] Array of template file names keyed by the template header name.
 */
function get_page_templates( $post = null, $post_type = 'page' ) {}
