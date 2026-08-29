<?php

/**
 * A function which gained an array parameter, and then a further argument within it.
 *
 * @since 1.0.0
 * @since 2.0.0 Added the `$args` parameter. The `$flavour` argument was added at the same time.
 * @since 3.0.0 The `$topping` argument was added.
 *
 * @param string $thing The thing.
 * @param array  $args {
 *     Optional. Arguments for the thing.
 *
 *     @type string $flavour Flavour of the thing.
 *     @type string $topping Topping for the thing.
 * }
 * @return bool Whether the thing was done.
 */
function wpcompat_test_parameter_with_keys( $thing, $args = array() ) {}
