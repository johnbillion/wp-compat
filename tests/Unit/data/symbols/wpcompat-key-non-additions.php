<?php

/**
 * A function whose changelog describes changes to arguments that already existed.
 *
 * @since 1.0.0
 * @since 2.0.0 Added the `$context` parameter.
 * @since 3.0.0 Added support for the `$orderby` argument.
 * @since 4.0.0 The `$number` argument is now optional.
 * @since 5.0.0 The `$exclude` argument was renamed to `$excluded`.
 *
 * @param string $thing   The thing.
 * @param string $context Optional. Context for the thing. Default empty.
 * @param array  $args {
 *     Optional. Arguments for the thing.
 *
 *     @type string $context  Context for the thing.
 *     @type string $orderby  Field to order the things by.
 *     @type int    $number   Number of things.
 *     @type int[]  $excluded Things to exclude.
 * }
 * @return bool Whether the thing was done.
 */
function wpcompat_test_key_non_additions( $thing, $context = '', $args = array() ) {}
