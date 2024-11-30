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
if ( isset( $argv[1] ) ) {
	$directory = rtrim( $argv[1], '/' );
} else {
	echo 'No directory name provided. Exiting...' . PHP_EOL;
	exit( 1 );
}

// Output file path
$output_file = __DIR__ . '/symbols.json';

// Create a new parser instance
$parser = ( new ParserFactory() )->createForNewestSupportedVersion();

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

// Iterate each PHP file in the directory
$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $directory ) );
foreach ( $files as $file ) {
	if ( $file->isFile() && $file->getExtension() === 'php' ) {
		$file_path = $file->getPathname();
		$relative_path = str_replace( $directory . '/', '', $file_path );

		// Check if the file is in an excluded directory
		foreach ( $excluded_paths as $excluded_path ) {
			if ( str_starts_with( $relative_path, $excluded_path ) ) {
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
				fn ( Node $node ) => ( $node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod )
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
					if ( ( $class instanceof Node\Stmt\Class_ ) && ( $class->name instanceof Node\Identifier ) ) {
						$function_name = $class->name->toString() . '::' . $function_name;
						$class_doc_comment = $class->getDocComment();
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
				if ( str_starts_with( $function_name, 'WP_Internal_Pointers::pointer_wp' ) ) {
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

				$result = [];

				if ( $deprecated !== null ) {
					$result['deprecated'] = $deprecated;
				}

				$result['since'] = $since;
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
