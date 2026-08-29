<?php declare(strict_types=1);

namespace WPCompat\PHPStan\Generator;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use WPCompat\PHPStan\ParameterAdditionMatcher;

/**
 * Extracts the symbol version data that gets written to symbols.json.
 */
final class SymbolExtractor {
	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * Informational messages about symbols that were skipped during the most recent extraction.
	 *
	 * @var list<string>
	 */
	private $notices = [];

	public function __construct( ?Parser $parser = null ) {
		$this->parser = $parser ?? self::createParser();
	}

	public static function createParser(): Parser {
		$factory = new ParserFactory();

		// @phpstan-ignore-next-line
		if ( method_exists( $factory, 'createForNewestSupportedVersion' ) ) {
			return $factory->createForNewestSupportedVersion();
		}

		/** @var callable $createMethod */
		$createMethod = array( $factory, 'create' );
		/** @var int $kind */
		$kind = defined( 'PhpParser\ParserFactory::PREFER_PHP7' ) ? (int) constant( 'PhpParser\ParserFactory::PREFER_PHP7' ) : 1;

		/** @var Parser */
		return call_user_func( $createMethod, $kind );
	}

	/**
	 * Extracts the symbols from the given PHP code.
	 *
	 * @param string $code      The PHP code to extract symbols from.
	 * @param string $file_path The path of the file that the code came from, used in notices.
	 * @return array<string, array{deprecated?: string, since: string, parameters?: array<string, array{since?: string, keys?: array<string, array{since: string}>}>}>
	 */
	public function extract( string $code, string $file_path = '' ): array {
		$this->notices = [];

		$stmts = $this->parser->parse( $code );

		if ( ! is_array( $stmts ) ) {
			throw new \Exception( 'Failed to parse code in ' . $file_path );
		}

		$visitor = new FindingVisitor(
			function ( Node $node ): bool {
				return $node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod;
			}
		);

		$traverser = new NodeTraverser();
		$traverser->addVisitor( $visitor );
		$traverser->addVisitor( new ParentConnectingVisitor() );
		$traverser->traverse( $stmts );

		/** @var list<Node\Stmt\Function_|Node\Stmt\ClassMethod> $functions */
		$functions = $visitor->getFoundNodes();

		$results = [];

		foreach ( $functions as $function ) {
			$symbol = $this->getSymbol( $function, $file_path );

			if ( $symbol === null ) {
				continue;
			}

			$results[ $symbol[0] ] = $symbol[1];
		}

		return $results;
	}

	/**
	 * Informational messages about symbols that were skipped during the most recent extraction.
	 *
	 * @return list<string>
	 */
	public function getNotices(): array {
		return $this->notices;
	}

	/**
	 * @param Node\Stmt\Function_|Node\Stmt\ClassMethod $node
	 * @return array{string, array{deprecated?: string, since: string, parameters?: array<string, array{since?: string, keys?: array<string, array{since: string}>}>}}|null
	 */
	private function getSymbol( Node $node, string $file_path ): ?array {
		$doc_comment = $node->getDocComment();
		$function_name = $node->name->toString();
		$class_doc_comment = null;

		if ( $node instanceof Node\Stmt\ClassMethod ) {
			$class = $node->getAttribute( 'parent' );
			if ( ( $class instanceof Node\Stmt\Class_ || $class instanceof Node\Stmt\Interface_ || $class instanceof Node\Stmt\Trait_ ) && ( $class->name instanceof Node\Identifier ) ) {
				$function_name = $class->name->toString() . '::' . $function_name;
				$class_doc_comment = $class->getDocComment();
			} else {
				// Anonymous class or unexpected parent — skip rather than emit a bare method name.
				return null;
			}

			// Ignore private methods.
			if ( $node->isPrivate() ) {
				return null;
			}
		}

		// This is a function defined within a function and is just plain messed up.
		if ( $function_name === 'wp_handle_upload_error' ) {
			return null;
		}

		// These are all stubs now.
		if ( 0 === strpos( $function_name, 'WP_Internal_Pointers::pointer_wp' ) ) {
			return null;
		}

		try {
			$deprecated = self::getDeprecatedFromDoc( $doc_comment );
		} catch ( \Exception $e ) {
			$deprecated = null;
		}

		try {
			$since = self::getSinceFromDocs( $class_doc_comment, $doc_comment );
		} catch ( MissingDocException | MissingTagException $e ) {
			if ( $deprecated === null ) {
				$this->notices[] = sprintf(
					'ℹ️ @since tag missing for %s() in %s:%d',
					$function_name,
					$file_path,
					$node->getStartLine()
				);
			}
			return null;
		} catch ( InvalidTagException $e ) {
			if ( $deprecated === null ) {
				$this->notices[] = sprintf(
					'ℹ️ Invalid @since value for %s() in %s:%d',
					$function_name,
					$file_path,
					$node->getStartLine()
				);
			}
			return null;
		}

		$param_names = [];
		foreach ( $node->params as $param ) {
			if ( $param->var instanceof Node\Expr\Variable && is_string( $param->var->name ) ) {
				$param_names[] = $param->var->name;
			}
		}

		$parameters = self::getParametersSinceFromDoc( $doc_comment, $param_names, $since );
		$parameter_keys = self::getParameterKeysSinceFromDoc( $doc_comment, self::getHashNotationKeys( $doc_comment ), $param_names, $since );

		foreach ( $parameter_keys as $parameter_name => $keys ) {
			// The hash notation is also used for documenting things other than parameters.
			if ( ! in_array( $parameter_name, $param_names, true ) ) {
				continue;
			}

			// A key is only worth recording when it postdates the parameter that holds it.
			if ( isset( $parameters[ $parameter_name ]['since'] ) ) {
				$parameter_since = $parameters[ $parameter_name ]['since'];
				$keys = array_filter(
					$keys,
					function ( array $key ) use ( $parameter_since ): bool {
						return version_compare( $key['since'], $parameter_since, '>' );
					}
				);
			}

			if ( $keys === [] ) {
				continue;
			}

			$parameters[ $parameter_name ]['keys'] = $keys;
		}

		ksort( $parameters );

		$result = [];

		if ( $deprecated !== null ) {
			$result['deprecated'] = $deprecated;
		}

		$result['since'] = $since;

		if ( $parameters !== [] ) {
			$result['parameters'] = $parameters;
		}

		return [ $function_name, $result ];
	}

	public static function getSinceFromDocs( ?Doc $class_doc, ?Doc $symbol_doc ): string {
		try {
			$class_since = self::getSinceFromDoc( $class_doc );
		} catch ( \Exception $e ) {
			$class_since = null;
		}

		try {
			$symbol_since = self::getSinceFromDoc( $symbol_doc );
		} catch ( \Exception $e ) {
			if ( is_string( $class_since ) ) {
				return $class_since;
			}

			throw $e;
		}

		return $symbol_since;
	}

	public static function getSinceFromDoc( ?Doc $doc ): string {
		if ( ! $doc instanceof Doc ) {
			throw new MissingDocException();
		}

		$comment_text = $doc->getText();

		if ( preg_match( '/@since\s+([\w.-]+)/', $comment_text, $matches ) !== 1 ) {
			throw new MissingTagException();
		}

		$since = $matches[1];

		if ( $since === 'MU' ) {
			$since = '3.0.0';
		}

		if ( preg_match( '/^\d+\.\d+(\.\d+)?$/', $since ) !== 1 ) {
			throw new InvalidTagException();
		}

		return $since;
	}

	public static function getDeprecatedFromDoc( ?Doc $doc ): string {
		if ( ! $doc instanceof Doc ) {
			throw new MissingDocException();
		}

		$comment_text = $doc->getText();

		if ( preg_match( '/@deprecated\s+([\w.-]+)/', $comment_text, $matches ) !== 1 ) {
			throw new MissingTagException();
		}

		if ( preg_match( '/^\d+\.\d+(\.\d+)?/', $matches[1], $since ) !== 1 ) {
			throw new InvalidTagException();
		}

		return $since[0];
	}

	/**
	 * Extracts the version and description of each changelog entry in a docblock.
	 *
	 * Only the entries which postdate the symbol are returned, since those are the ones which
	 * describe a change to it rather than its introduction. Entries without a description are
	 * skipped too, as there's nothing in them to attribute a change to.
	 *
	 * @return list<array{version: string, description: string}>
	 */
	public static function getSinceEntriesFromDoc( ?Doc $doc, string $symbol_since ): array {
		if ( ! $doc instanceof Doc ) {
			return [];
		}

		$comment_text = $doc->getText();

		if ( strpos( $comment_text, '@since' ) === false ) {
			return [];
		}

		if ( preg_match_all( '/@since\s+([\w.-]+)(?:[ \t]+([^\r\n]+))?/', $comment_text, $matches, PREG_SET_ORDER ) === 0 ) {
			return [];
		}

		$entries = [];

		foreach ( $matches as $match ) {
			$version = $match[1];

			if ( $version === 'MU' ) {
				$version = '3.0.0';
			}

			if ( preg_match( '/^\d+\.\d+(\.\d+)?$/', $version ) !== 1 ) {
				continue;
			}

			if ( version_compare( $version, $symbol_since, '<=' ) ) {
				continue;
			}

			$description = $match[2] ?? '';

			if ( $description === '' ) {
				continue;
			}

			$entries[] = [
				'version'     => $version,
				'description' => $description,
			];
		}

		return $entries;
	}

	/**
	 * @param list<string> $param_names
	 * @return array<string, array{since: string}>
	 */
	public static function getParametersSinceFromDoc( ?Doc $doc, array $param_names, string $symbol_since ): array {
		if ( $param_names === [] ) {
			return [];
		}

		$parameters = [];

		foreach ( self::getSinceEntriesFromDoc( $doc, $symbol_since ) as $entry ) {
			foreach ( $param_names as $pname ) {
				if ( ! ParameterAdditionMatcher::matches( $entry['description'], $pname ) ) {
					continue;
				}

				if ( ! isset( $parameters[ $pname ] ) || version_compare( $entry['version'], $parameters[ $pname ]['since'], '<' ) ) {
					$parameters[ $pname ] = [ 'since' => $entry['version'] ];
				}
			}
		}

		ksort( $parameters );

		return $parameters;
	}

	/**
	 * Extracts the array keys that are documented for each array shaped parameter.
	 *
	 * WordPress documents the elements of an array parameter with the hash notation, in which the
	 * parameter description is followed by a list of `@type` tags wrapped in braces. Nested arrays
	 * are documented with a nested hash, so keys are returned as dot delimited paths.
	 *
	 * @see https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/#1-1-parameters-that-are-arrays
	 *
	 * @return array<string, list<string>> Array of key paths keyed by parameter name.
	 */
	public static function getHashNotationKeys( ?Doc $doc ): array {
		if ( ! $doc instanceof Doc ) {
			return [];
		}

		$lines = preg_split( '/\R/', $doc->getText() );

		if ( ! is_array( $lines ) ) {
			return [];
		}

		$keys = [];
		$parameter = null;
		$path = [];

		foreach ( $lines as $line ) {
			// Strip the leading whitespace and the docblock asterisk.
			$line = trim( (string) preg_replace( '#^\s*(?:/\*\*|\*/|\*)#', '', $line ) );

			if ( $parameter === null ) {
				if ( preg_match( '/^@param\s+.+?\s+\$([A-Za-z_][A-Za-z0-9_]*)\s*\{$/', $line, $matches ) === 1 ) {
					$parameter = $matches[1];
					$path = [];
				}

				continue;
			}

			if ( $line === '}' ) {
				if ( $path === [] ) {
					$parameter = null;
				} else {
					array_pop( $path );
				}

				continue;
			}

			if ( preg_match( '/^@type\s+.+?\s+\$([A-Za-z_][A-Za-z0-9_-]*)(?:\s|$)/', $line, $matches ) !== 1 ) {
				continue;
			}

			$key = $matches[1];
			$keys[ $parameter ][] = implode( '.', array_merge( $path, [ $key ] ) );

			// A trailing brace means this key is documented with a nested hash of its own.
			if ( substr( $line, -1 ) === '{' ) {
				$path[] = $key;
			}
		}

		return $keys;
	}

	/**
	 * Determines which of the documented array keys were introduced after the symbol itself.
	 *
	 * @param array<string, list<string>> $parameter_keys Array of key paths keyed by parameter name.
	 * @param list<string> $param_names
	 * @return array<string, array<string, array{since: string}>> Array of key paths and their versions, keyed by parameter name.
	 */
	public static function getParameterKeysSinceFromDoc( ?Doc $doc, array $parameter_keys, array $param_names, string $symbol_since ): array {
		if ( $parameter_keys === [] ) {
			return [];
		}

		// Index every documented key by its own name so that a changelog entry which mentions
		// "the `name` argument" can be resolved to the `before.name` key path.
		$candidates = [];

		foreach ( $parameter_keys as $parameter => $paths ) {
			foreach ( $paths as $path ) {
				$parts = explode( '.', $path );
				$candidates[ end( $parts ) ][] = [ $parameter, $path ];
			}
		}

		$keys = [];

		foreach ( self::getSinceEntriesFromDoc( $doc, $symbol_since ) as $entry ) {
			foreach ( $candidates as $key => $paths ) {
				// A key that shares its name with a parameter is left to getParametersSinceFromDoc().
				if ( in_array( $key, $param_names, true ) ) {
					continue;
				}

				if ( ! ParameterKeyAdditionMatcher::matches( $entry['description'], $key ) ) {
					continue;
				}

				$resolved = self::resolveKeyPath( $paths );

				if ( $resolved === null ) {
					continue;
				}

				list( $parameter, $path ) = $resolved;

				if ( ! isset( $keys[ $parameter ][ $path ] ) || version_compare( $entry['version'], $keys[ $parameter ][ $path ]['since'], '<' ) ) {
					$keys[ $parameter ][ $path ] = [ 'since' => $entry['version'] ];
				}
			}
		}

		foreach ( $keys as $parameter => $paths ) {
			ksort( $paths );
			$keys[ $parameter ] = $paths;
		}

		ksort( $keys );

		return $keys;
	}

	/**
	 * Picks the key path that a changelog entry refers to.
	 *
	 * The same key name can be documented more than once, for example as both a top level key and
	 * a key of a nested array. The shallowest one wins, and nothing is returned when that is
	 * ambiguous.
	 *
	 * @param list<array{0: string, 1: string}> $paths
	 * @return array{0: string, 1: string}|null
	 */
	private static function resolveKeyPath( array $paths ): ?array {
		if ( count( $paths ) === 1 ) {
			return $paths[0];
		}

		usort(
			$paths,
			function ( array $a, array $b ): int {
				return substr_count( $a[1], '.' ) <=> substr_count( $b[1], '.' );
			}
		);

		if ( substr_count( $paths[0][1], '.' ) === substr_count( $paths[1][1], '.' ) ) {
			return null;
		}

		return $paths[0];
	}
}
