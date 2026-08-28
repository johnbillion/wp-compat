<?php
#!/usr/bin/env php

namespace WPCompat\PHPStan;

use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use WPCompat\PHPStan\Generator\MissingDocException;
use WPCompat\PHPStan\Generator\MissingTagException;
use WPCompat\PHPStan\Generator\InvalidTagException;

require 'vendor/autoload.php';

// Directory to scan
if ( isset( $argv[1] ) && is_dir( $argv[1] ) ) {
	$directory = rtrim( $argv[1], '/' );
} else {
	echo 'Error: Please provide a valid directory path to a WordPress installation.' . PHP_EOL;
	echo 'Usage: composer generate /path/to/wordpress' . PHP_EOL;
	exit( 1 );
}

// Output file path
$output_file = __DIR__ . '/symbols.json';

// Create a new parser instance
$factory = new ParserFactory();
// @phpstan-ignore-next-line
if ( method_exists( $factory, 'createForNewestSupportedVersion' ) ) {
	$parser = $factory->createForNewestSupportedVersion();
} else {
	/** @var callable $createMethod */
	$createMethod = array( $factory, 'create' );
	/** @var int $kind */
	$kind   = defined( 'PhpParser\ParserFactory::PREFER_PHP7' ) ? (int) constant( 'PhpParser\ParserFactory::PREFER_PHP7' ) : 1;
	$parser = call_user_func( $createMethod, $kind );
}

// Initialize an array to store the results
$results = array();

// List of directories to exclude
// @TODO hardcode the @since versions for symbols in these files
$excluded_paths = array(
	// wp-content:
	'wp-content/',
	// wp-admin:
	'wp-admin/includes/class-pclzip.php',
	'wp-admin/includes/noop.php',
	// wp-includes directories:
	'wp-includes/ID3/',
	'wp-includes/IXR/',
	'wp-includes/php-ai-client/src/',
	'wp-includes/PHPMailer/',
	'wp-includes/pomo/',
	'wp-includes/Requests/',
	'wp-includes/SimplePie/',
	'wp-includes/Text/',
	'wp-includes/sodium_compat/',
	'wp-includes/js/tinymce',
	// wp-includes files:
	'wp-includes/class-simplepie.php',
	'wp-includes/atomlib.php',
	'wp-includes/class-avif-info.php',
	'wp-includes/class-json.php',
	'wp-includes/class-pop3.php',
	'wp-includes/class-requests.php',
	'wp-includes/class-snoopy.php',
	'wp-includes/compat.php',
	'wp-includes/rss.php',
);

echo 'Scanning and collating symbols...' . PHP_EOL;

function getSinceFromDocs( ?Doc $class_doc, ?Doc $symbol_doc ): string {
	try {
		$class_since = getSinceFromDoc( $class_doc );
	} catch ( \Exception $e ) {
		$class_since = null;
	}

	try {
		$symbol_since = getSinceFromDoc( $symbol_doc );
	} catch ( \Exception $e ) {
		if ( is_string( $class_since ) ) {
			return $class_since;
		}

		throw $e;
	}

	return $symbol_since;
}

function getSinceFromDoc( ?Doc $doc ): string {
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

function getDeprecatedFromDoc( ?Doc $doc ): string {
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
 * Only the entries which postdate the symbol are returned, since those are the ones which describe
 * a change to it rather than its introduction. Entries without a description are skipped too, as
 * there's nothing in them to attribute a change to.
 *
 * @return list<array{version: string, description: string}>
 */
function getSinceEntriesFromDoc( ?Doc $doc, string $symbol_since ): array {
	if ( ! $doc instanceof Doc ) {
		return [];
	}

	$comment_text = $doc->getText();

	if ( ! str_contains( $comment_text, '@since' ) ) {
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
function getParametersSinceFromDoc( ?Doc $doc, array $param_names, string $symbol_since ): array {
	if ( $param_names === [] ) {
		return [];
	}

	$parameters = [];

	foreach ( getSinceEntriesFromDoc( $doc, $symbol_since ) as $entry ) {
		$version = $entry['version'];
		$description = $entry['description'];

		foreach ( $param_names as $pname ) {
			$escaped_pname = preg_quote( $pname, '/' );
			if (
				preg_match( '/(?:Added|Introduced|Formalized|added)\s+.*(?:\$' . $escaped_pname . '|`\$?' . $escaped_pname . '`|\b' . $escaped_pname . '\b).*(?:parameter|argument)/i', $description ) === 1 ||
				preg_match( '/(?:\$' . $escaped_pname . '|`\$?' . $escaped_pname . '`|\b' . $escaped_pname . '\b).*(?:parameter|argument).*(?:added|introduced)/i', $description ) === 1 ||
				preg_match( '/(?:The\s+)?(?:\$' . $escaped_pname . '|`\$?' . $escaped_pname . '`).*(?:parameter|argument)?\s+was\s+added/i', $description ) === 1
			) {
				if ( ! isset( $parameters[ $pname ] ) || version_compare( $version, $parameters[ $pname ]['since'], '<' ) ) {
					$parameters[ $pname ] = [ 'since' => $version ];
				}
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
function getHashNotationKeys( ?Doc $doc ): array {
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
function getParameterKeysSinceFromDoc( ?Doc $doc, array $parameter_keys, array $param_names, string $symbol_since ): array {
	if ( $parameter_keys === [] ) {
		return [];
	}

	// Index every documented key by its own name so that a changelog entry which mentions
	// "the `public` meta argument" can be resolved to the `meta.public` key path.
	$candidates = [];

	foreach ( $parameter_keys as $parameter => $paths ) {
		foreach ( $paths as $path ) {
			$parts = explode( '.', $path );
			$candidates[ end( $parts ) ][] = [ $parameter, $path ];
		}
	}

	$keys = [];

	foreach ( getSinceEntriesFromDoc( $doc, $symbol_since ) as $entry ) {
		$version = $entry['version'];

		// A changelog entry often describes several unrelated changes, so each sentence is
		// considered on its own.
		$sentences = preg_split( '/(?<=[.;])\s+/', $entry['description'] );

		if ( ! is_array( $sentences ) ) {
			continue;
		}

		foreach ( $sentences as $sentence ) {
			// Sentences such as "Added support for `$operator`" or "Added the ability to order by
			// the `include` value" describe a change to an existing key rather than a new one.
			if ( preg_match( '/\b(?:support for|ability to|order(?:ing)? by|no longer|renamed|removed|deprecated|(?:is|are|now) (?:now|accepts?|supports?|defaults?))\b/i', $sentence ) === 1 ) {
				continue;
			}

			foreach ( $candidates as $key => $paths ) {
				// A key that shares its name with a parameter is left to getParametersSinceFromDoc().
				if ( in_array( $key, $param_names, true ) ) {
					continue;
				}

				// Keys are only ever referenced in backticks or with a dollar sign. Matching bare
				// words would attribute far too many changelog entries to a key.
				$mention = '(?:`\$?' . preg_quote( $key, '/' ) . '`|\$' . preg_quote( $key, '/' ) . '\b)';

				if (
					preg_match( '/(?:Added|Introduced)\b.*?' . $mention . '/i', $sentence ) !== 1 &&
					preg_match( '/' . $mention . '.*?\b(?:added|introduced)\b/i', $sentence ) !== 1
				) {
					continue;
				}

				$resolved = resolveKeyPath( $paths );

				if ( $resolved === null ) {
					continue;
				}

				list( $parameter, $path ) = $resolved;

				if ( ! isset( $keys[ $parameter ][ $path ] ) || version_compare( $version, $keys[ $parameter ][ $path ]['since'], '<' ) ) {
					$keys[ $parameter ][ $path ] = [ 'since' => $version ];
				}
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
 * The same key name can be documented more than once, for example as both a top level key and a
 * key of a nested array. The shallowest one wins, and nothing is returned when that is ambiguous.
 *
 * @param list<array{0: string, 1: string}> $paths
 * @return array{0: string, 1: string}|null
 */
function resolveKeyPath( array $paths ): ?array {
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

// Iterate each PHP file in the directory
$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $directory ) );
foreach ( $files as $file ) {
	if ( $file->isFile() && $file->getExtension() === 'php' ) {
		$file_path = $file->getPathname();
		$relative_path = str_replace( $directory . '/', '', $file_path );

		// Check if the file is in an excluded directory
		foreach ( $excluded_paths as $excluded_path ) {
			if ( 0 === strpos( $relative_path, $excluded_path ) ) {
				continue 2;
			}
		}

		// Parse the PHP file
		$contents = file_get_contents( $file );

		if ( $contents === false ) {
			throw new \Exception( 'Failed to read file ' . $file );
		}

		$stmts = $parser->parse( $contents );

		if ( ! is_array( $stmts ) ) {
			throw new \Exception( 'Failed to parse file ' . $file );
		}

		try {
			// Find all function and method nodes
			// Create a new FindingVisitor instance
			$visitor = new FindingVisitor(
				function ( Node $node ): bool {
					return $node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod;
				}
			);

			// Traverse the AST and find all function and method nodes
			$traverser = new NodeTraverser();
			$traverser->addVisitor( $visitor );
			$traverser->addVisitor( new ParentConnectingVisitor() );
			$traverser->traverse( $stmts );

			// Get the found functions and methods
			/** @var list<Node\Stmt\Function_|Node\Stmt\ClassMethod> $functions */
			$functions = $visitor->getFoundNodes();

			// Extract the function and method names along with their @since values
			foreach ( $functions as $function ) {
				$doc_comment = $function->getDocComment();
				$function_name = $function->name->toString();
				$class_doc_comment = null;

				if ( $function instanceof Node\Stmt\ClassMethod ) {
					$class = $function->getAttribute( 'parent' );
					if ( ( $class instanceof Node\Stmt\Class_ || $class instanceof Node\Stmt\Interface_ || $class instanceof Node\Stmt\Trait_ ) && ( $class->name instanceof Node\Identifier ) ) {
						$function_name = $class->name->toString() . '::' . $function_name;
						$class_doc_comment = $class->getDocComment();
					} else {
						// Anonymous class or unexpected parent — skip rather than emit a bare method name.
						continue;
					}
				}

				// Ignore private methods.
				if ( $function instanceof Node\Stmt\ClassMethod && $function->isPrivate() ) {
					continue;
				}

				// This is a function defined within a function and is just plain messed up.
				if ( $function_name === 'wp_handle_upload_error' ) {
					continue;
				}

				// These are all stubs now.
				if ( 0 === strpos( $function_name, 'WP_Internal_Pointers::pointer_wp' ) ) {
					continue;
				}

				try {
					$deprecated = getDeprecatedFromDoc( $doc_comment );
				} catch ( \Exception $e ) {
					$deprecated = null;
				}

				try {
					$since = getSinceFromDocs( $class_doc_comment, $doc_comment );
				} catch ( MissingDocException | MissingTagException $e ) {
					if ( $deprecated === null ) {
						printf(
							'ℹ️ @since tag missing for %s() in %s:%d' . PHP_EOL,
							$function_name,
							$file_path,
							$function->getStartLine(),
						);
					}
					continue;
				} catch ( InvalidTagException $e ) {
					if ( $deprecated === null ) {
						printf(
							'ℹ️ Invalid @since value for %s() in %s:%d' . PHP_EOL,
							$function_name,
							$file_path,
							$function->getStartLine(),
						);
					}
					continue;
				}

				$param_names = [];
				foreach ( $function->params as $param ) {
					if ( $param->var instanceof Node\Expr\Variable && is_string( $param->var->name ) ) {
						$param_names[] = $param->var->name;
					}
				}

				$parameters = getParametersSinceFromDoc( $doc_comment, $param_names, $since );
				$parameter_keys = getParameterKeysSinceFromDoc( $doc_comment, getHashNotationKeys( $doc_comment ), $param_names, $since );

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

				$results[ $function_name ] = $result;
			}
		} catch ( Error $e ) {
			// Handle parsing errors
			throw new \Exception( 'Error parsing file: ' . $e->getMessage() );
		}
	}
}

ksort( $results );

echo 'Scanning complete, writing data.' . PHP_EOL;

// Write the results to the output file
$data = array(
	'$schema' => 'https://raw.githubusercontent.com/johnbillion/wp-compat/trunk/schemas/symbols.json',
	'symbols' => $results,
);
$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

$written = file_put_contents( $output_file, $json );

if ( $written === false ) {
	echo '❌ Failed to write symbols to symbols.json.' . PHP_EOL;
	exit( 1 );
}

echo '✅ Symbols written to symbols.json.' . PHP_EOL;
