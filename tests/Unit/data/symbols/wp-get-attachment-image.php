<?php

/*
 * The docblock and signature of wp_get_attachment_image(), copied verbatim from WordPress core:
 * wp-includes/media.php. The function body is omitted as it's not used.
 */

/**
 * Gets an HTML img element representing an image attachment.
 *
 * While `$size` will accept an array, it is better to register a size with
 * add_image_size() so that a cropped version is generated. It's much more
 * efficient than having to find the closest-sized image and then having the
 * browser scale down the image.
 *
 * @since 2.5.0
 * @since 4.4.0 The `$srcset` and `$sizes` attributes were added.
 * @since 5.5.0 The `$loading` attribute was added.
 * @since 6.1.0 The `$decoding` attribute was added.
 *
 * @param int          $attachment_id Image attachment ID.
 * @param string|int[] $size          Optional. Image size. Accepts any registered image size name, or an array
 *                                    of width and height values in pixels (in that order). Default 'thumbnail'.
 * @param bool         $icon          Optional. Whether the image should be treated as an icon. Default false.
 * @param string|array $attr {
 *     Optional. Attributes for the image markup.
 *
 *     @type string       $src           Image attachment URL.
 *     @type string       $class         CSS class name or space-separated list of classes.
 *                                       Default `attachment-$size_class size-$size_class`,
 *                                       where `$size_class` is the image size being requested.
 *     @type string       $alt           Image description for the alt attribute.
 *     @type string       $srcset        The 'srcset' attribute value.
 *     @type string       $sizes         The 'sizes' attribute value.
 *     @type string|false $loading       The 'loading' attribute value. Passing a value of false
 *                                       will result in the attribute being omitted for the image.
 *                                       Default determined by {@see wp_get_loading_optimization_attributes()}.
 *     @type string       $decoding      The 'decoding' attribute value. Possible values are
 *                                       'async' (default), 'sync', or 'auto'. Passing false or an empty
 *                                       string will result in the attribute being omitted.
 *     @type string       $fetchpriority The 'fetchpriority' attribute value, whether `high`, `low`, or `auto`.
 *                                       Default determined by {@see wp_get_loading_optimization_attributes()}.
 * }
 * @return string HTML img element or empty string on failure.
 */
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = '' ) {}
