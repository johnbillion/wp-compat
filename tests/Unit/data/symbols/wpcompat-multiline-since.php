<?php

/**
 * A function whose changelog entries are too long to fit on one line.
 *
 * @since 1.0.0
 * @since 2.0.0 Added the `$flavour`, `$topping`, and
 *              `$sprinkles` arguments.
 * @since 3.0.0 Added the `$sauce` argument.
 *              Deprecated the `$cone` argument.
 * @since 4.0.0 Introduced `RAND(x)` syntax for `$order`.
 *
 * @param array $args {
 *     Optional. Arguments for the thing.
 *
 *     @type string $flavour   Flavour of the thing.
 *     @type string $topping   Topping for the thing.
 *     @type string $sprinkles Sprinkles for the thing.
 *     @type string $sauce     Sauce for the thing.
 *     @type string $cone      Cone for the thing.
 *     @type string $order     Field to order the things by.
 * }
 * @return bool Whether the thing was done.
 */
function wpcompat_test_multiline_since( $args = array() ) {}
