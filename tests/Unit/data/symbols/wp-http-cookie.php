<?php

/*
 * The docblock and signature of WP_Http_Cookie::__construct(), copied verbatim from
 * WordPress core: wp-includes/class-wp-http-cookie.php. The method body and the rest of
 * the class are omitted as they're not used.
 */

/**
 * Core class used to encapsulate a single cookie object for internal use.
 *
 * Returned cookies are represented using this class, and when cookies are set, if they are not
 * already a WP_Http_Cookie() object, then they are turned into one.
 *
 * @todo The WordPress convention is to use underscores instead of camelCase for function and method
 * names. Need to switch to use underscores instead for the methods.
 *
 * @since 2.8.0
 */
#[AllowDynamicProperties]
class WP_Http_Cookie {

	/**
	 * Sets up this cookie object.
	 *
	 * The parameter $data should be either an associative array containing the indices names below
	 * or a header string detailing it.
	 *
	 * @since 2.8.0
	 * @since 5.2.0 Added `host_only` to the `$data` parameter.
	 *
	 * @param string|array $data {
	 *     Raw cookie data as header string or data array.
	 *
	 *     @type string          $name      Cookie name.
	 *     @type mixed           $value     Value. Should NOT already be urlencoded.
	 *     @type string|int|null $expires   Optional. Unix timestamp or formatted date. Default null.
	 *     @type string          $path      Optional. Path. Default '/'.
	 *     @type string          $domain    Optional. Domain. Default host of parsed $requested_url.
	 *     @type int|string      $port      Optional. Port or comma-separated list of ports. Default null.
	 *     @type bool            $host_only Optional. host-only storage flag. Default true.
	 * }
	 * @param string       $requested_url The URL which the cookie was set on, used for default $domain
	 *                                    and $port values.
	 */
	public function __construct( $data, $requested_url = '' ) {}
}
