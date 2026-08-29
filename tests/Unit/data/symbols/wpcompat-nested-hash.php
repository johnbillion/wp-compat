<?php

/**
 * A function whose arguments contain nested arrays of their own.
 *
 * @since 1.0.0
 * @since 2.0.0 The `$name` argument was added.
 * @since 3.0.0 The `$label` argument was added.
 *
 * @param array $args {
 *     Optional. Arguments for the thing.
 *
 *     @type string $type   The type of the thing.
 *     @type array  $before {
 *         Optional. Arguments for the markup before the thing.
 *
 *         @type string $name  Name for the markup.
 *         @type string $label Label for the markup.
 *     }
 *     @type array  $after {
 *         Optional. Arguments for the markup after the thing.
 *
 *         @type string $label Label for the markup.
 *     }
 * }
 * @return bool Whether the thing was done.
 */
function wpcompat_test_nested_hash( $args = array() ) {}
