<?php

/*
 * The docblock and signature of get_user_by(), copied verbatim from WordPress core:
 * wp-includes/pluggable.php. The function body is omitted as it's not used.
 */

/**
 * Retrieves user info by a given field.
 *
 * @since 2.8.0
 * @since 4.4.0 Added 'ID' as an alias of 'id' for the `$field` parameter.
 *
 * @global WP_User $current_user The current user object which holds the user data.
 *
 * @param string     $field The field to retrieve the user with. id | ID | slug | email | login.
 * @param int|string $value A value for $field. A user ID, slug, email address, or login name.
 * @return WP_User|false WP_User object on success, false on failure.
 */
function get_user_by( $field, $value ) {}
