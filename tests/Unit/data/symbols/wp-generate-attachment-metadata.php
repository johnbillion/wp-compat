<?php

/*
 * The docblock and signature of wp_generate_attachment_metadata(), copied verbatim from WordPress core:
 * wp-admin/includes/image.php. The function body is omitted as it's not used.
 */

/**
 * Generates attachment meta data and create image sub-sizes for images.
 *
 * @since 2.1.0
 * @since 6.0.0 The `$filesize` value was added to the returned array.
 * @since 6.7.0 The 'image/heic' mime type is supported.
 *
 * @param int    $attachment_id Attachment ID to process.
 * @param string $file          Filepath of the attached image.
 * @return array Metadata for attachment.
 */
function wp_generate_attachment_metadata( $attachment_id, $file ) {}
