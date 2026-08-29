<?php declare(strict_types=1);

namespace WPCompat\PHPStan;

/**
 * Determines whether the description of an `@since` tag states that a given parameter was added.
 */
final class ParameterAdditionMatcher {
	public static function matches( string $description, string $param ): bool {
		$escaped = preg_quote( $param, '/' );

		// Matches the parameter name in its dollar prefixed, backticked, or bare word forms.
		$name = '(?:\$' . $escaped . '|`\$?' . $escaped . '`|\b' . $escaped . ')(?![A-Za-z0-9_])';

		// As above but without the bare word form, which is too loose for descriptions such as
		// "A return value was added".
		$variable = '(?:\$' . $escaped . '|`\$?' . $escaped . '`)(?![A-Za-z0-9_])';

		foreach ( self::getSentences( $description ) as $sentence ) {
			// The parameter is the destination of the change rather than the thing that was
			// added, for example "Added `host_only` to the `$data` parameter".
			if ( preg_match( '/\b(?:to|for|in|of|from)\s+(?:the\s+)?' . $name . '/i', $sentence ) === 1 ) {
				continue;
			}

			if (
				preg_match( '/(?:Added|Introduced|Formalized|added)\s+.*' . $name . '.*(?:parameter|argument)/i', $sentence ) === 1 ||
				preg_match( '/' . $name . '.*(?:parameter|argument).*(?:added|introduced)/i', $sentence ) === 1 ||
				preg_match( '/(?:The\s+)?' . $variable . '.*(?:parameter|argument)?\s+was\s+added/i', $sentence ) === 1
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Splits a description into sentences so that a verb in one sentence can't be paired with a
	 * parameter name in another.
	 *
	 * @return list<string>
	 */
	private static function getSentences( string $description ): array {
		$sentences = preg_split( '/(?<=\.)\s+(?=[A-Z`$\'"])/', $description );

		if ( $sentences === false ) {
			return [ $description ];
		}

		return $sentences;
	}
}
