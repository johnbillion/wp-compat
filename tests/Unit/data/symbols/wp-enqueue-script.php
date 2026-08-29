<?php

/*
 * The docblock and signature of wp_enqueue_script(), copied verbatim from WordPress core:
 * wp-includes/functions.wp-scripts.php. The function body is omitted as it's not used.
 */

/**
 * Enqueues a script.
 *
 * Registers the script if `$src` provided (does NOT overwrite), and enqueues it.
 *
 * @see WP_Dependencies::add()
 * @see WP_Dependencies::add_data()
 * @see WP_Dependencies::enqueue()
 *
 * @since 2.1.0
 * @since 6.3.0 The $in_footer parameter of type boolean was overloaded to be an $args parameter of type array.
 * @since 6.9.0 The $fetchpriority parameter of type string was added to the $args parameter of type array.
 * @since 7.0.0 The $module_dependencies parameter of type string[] was added to the $args parameter of type array.
 *
 * @param string           $handle Name of the script. Should be unique.
 * @param string           $src    Full URL of the script, or path of the script relative to the WordPress root directory.
 *                                 Default empty.
 * @param string[]         $deps   Optional. An array of registered script handles this script depends on. Default empty array.
 * @param string|bool|null $ver    Optional. String specifying script version number, if it has one, which is added to the URL
 *                                 as a query string for cache busting purposes. If version is set to false, a version
 *                                 number is automatically added equal to current installed WordPress version.
 *                                 If set to null, no version is added.
 * @param array|bool $args {
 *     Optional. An array of extra args for the script. Default empty array.
 *     Otherwise, it may be a boolean in which case it determines whether the script is printed in the footer. Default false.
 *
 *     @type string $strategy            Optional. If provided, may be either 'defer' or 'async'.
 *     @type bool   $in_footer           Optional. Whether to print the script in the footer. Default 'false'.
 *     @type string $fetchpriority       Optional. The fetch priority for the script. Default 'auto'.
 *     @type array  $module_dependencies Optional. IDs for module dependencies loaded via dynamic import. Default empty array.
 *                                       For the full data format, see the `$deps` param of {@see wp_register_script_module()}.
 *                                       When provided, the script must either be printed in the footer (with
 *                                       `in_footer` set to true) or use a deferred loading `strategy` (`defer`),
 *                                       so that the script modules import map is printed before the script
 *                                       is evaluated. Otherwise dynamic imports may fail to resolve.
 * }
 *
 * @phpstan-param non-empty-string $handle
 * @phpstan-param string $src
 * @phpstan-param non-empty-string[] $deps
 * @phpstan-param array{
 *     in_footer?: bool,
 *     strategy?: 'async'|'defer',
 *     fetchpriority?: 'low'|'auto'|'high',
 *     module_dependencies?: array<non-empty-string|array{ id: non-empty-string, ... }>,
 * }|bool $args
 */
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $args = array() ) {}
