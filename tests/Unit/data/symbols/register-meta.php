<?php

/*
 * The docblock and signature of register_meta(), copied verbatim from WordPress core:
 * wp-includes/meta.php. The function body is omitted as it's not used.
 */

/**
 * Registers a meta key.
 *
 * It is recommended to register meta keys for a specific combination of object type and object subtype. If passing
 * an object subtype is omitted, the meta key will be registered for the entire object type, however it can be partly
 * overridden in case a more specific meta key of the same name exists for the same object type and a subtype.
 *
 * If an object type does not support any subtypes, such as blogs, users, or comments, you should commonly call this function
 * without passing a subtype.
 *
 * @since 3.3.0
 * @since 4.6.0 {@link https://core.trac.wordpress.org/ticket/35658 Modified
 *              to support an array of data to attach to registered meta keys}. Previous arguments for
 *              `$sanitize_callback` and `$auth_callback` have been folded into this array.
 * @since 4.9.8 The `$object_subtype` argument was added to the arguments array.
 * @since 5.3.0 Valid meta types expanded to include "array" and "object".
 * @since 5.5.0 The `$default` argument was added to the arguments array.
 * @since 6.4.0 The `$revisions_enabled` argument was added to the arguments array.
 * @since 6.7.0 The `label` argument was added to the arguments array.
 *
 * @global array $wp_meta_keys Global registry for meta keys.
 *
 * @param string       $object_type Type of object metadata is for. Accepts 'blog', 'post', 'comment', 'term',
 *                                  'user', or any other object type with an associated meta table.
 * @param string       $meta_key    Meta key to register.
 * @param array        $args {
 *     Data used to describe the meta key when registered.
 *
 *     @type string     $object_subtype    A subtype; e.g. if the object type is "post", the post type. If left empty,
 *                                         the meta key will be registered on the entire object type. Default empty.
 *     @type string     $type              The type of data associated with this meta key.
 *                                         Valid values are 'string', 'boolean', 'integer', 'number', 'array', and 'object'.
 *     @type string     $label             A human-readable label of the data attached to this meta key.
 *     @type string     $description       A description of the data attached to this meta key.
 *     @type bool       $single            Whether the meta key has one value per object, or an array of values per object.
 *     @type mixed      $default           The default value returned from get_metadata() if no value has been set yet.
 *                                         When using a non-single meta key, the default value is for the first entry.
 *                                         In other words, when calling get_metadata() with `$single` set to `false`,
 *                                         the default value given here will be wrapped in an array.
 *     @type callable   $sanitize_callback A function or method to call when sanitizing `$meta_key` data.
 *     @type callable   $auth_callback     Optional. A function or method to call when performing edit_post_meta,
 *                                         add_post_meta, and delete_post_meta capability checks.
 *     @type bool|array $show_in_rest      Whether data associated with this meta key can be considered public and
 *                                         should be accessible via the REST API. A custom post type must also declare
 *                                         support for custom fields for registered meta to be accessible via REST.
 *                                         When registering complex meta values this argument may optionally be an
 *                                         array with 'schema' or 'prepare_callback' keys instead of a boolean.
 *     @type bool       $revisions_enabled Whether to enable revisions support for this meta_key. Can only be used when the
 *                                         object type is 'post'.
 * }
 * @param string|array $deprecated Deprecated. Use `$args` instead.
 * @return bool True if the meta key was successfully registered in the global array, false if not.
 *              Registering a meta key with distinct sanitize and auth callbacks will fire those callbacks,
 *              but will not add to the global registry.
 */
function register_meta( $object_type, $meta_key, $args, $deprecated = null ) {}
