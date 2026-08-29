<?php declare(strict_types=1);

namespace WPCompat\PHPStan\Generator;

/**
 * Determines whether the description of an `@since` tag states that a given array key was added to
 * one of the parameters of a symbol.
 */
final class ParameterKeyAdditionMatcher {
	public static function matches( string $description, string $key ): bool {
		$escaped = preg_quote( $key, '/' );

		// Keys are only ever referenced in backticks or with a dollar sign. Matching bare words
		// would attribute far too many changelog entries to a key.
		$mention = '(?:`\$?' . $escaped . '`|\$' . $escaped . ')(?![A-Za-z0-9_])';

		foreach ( self::getSentences( $description ) as $sentence ) {
			// Sentences such as "Added support for `$operator`" or "Added the ability to order by
			// the `include` value" describe a change to an existing key rather than a new one.
			if ( preg_match( '/\b(?:support for|ability to|order(?:ing)? by|no longer|renamed|removed|deprecated|(?:is|are|now) (?:now|accepts?|supports?|defaults?))\b/i', $sentence ) === 1 ) {
				continue;
			}

			if (
				preg_match( '/(?:Added|Introduced)\b.*?' . $mention . '/i', $sentence ) === 1 ||
				preg_match( '/' . $mention . '.*?\b(?:added|introduced)\b/i', $sentence ) === 1
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Splits a description into sentences so that a verb in one sentence can't be paired with a
	 * key name in another.
	 *
	 * Changelog entries often use a semicolon to separate several unrelated changes, so those are
	 * treated as sentence boundaries too.
	 *
	 * @return list<string>
	 */
	private static function getSentences( string $description ): array {
		$sentences = preg_split( '/(?<=[.;])\s+/', $description );

		if ( $sentences === false ) {
			return [ $description ];
		}

		return $sentences;
	}
}
