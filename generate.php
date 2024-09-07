<?php
#!/usr/bin/env php

namespace WPCompat;

use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;

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
	'wp-content/',
	'wp-admin/includes/class-pclzip.php',
	'wp-admin/includes/noop.php',
	'wp-includes/class-simplepie.php',
	'wp-includes/ID3/',
	'wp-includes/IXR/',
	'wp-includes/PHPMailer/',
	'wp-includes/SimplePie/',
	'wp-includes/Text/',
	'wp-includes/sodium_compat/',
	'wp-includes/atomlib.php',
	'wp-includes/class-json.php',
	'wp-includes/compat.php',
);

echo 'Scanning and collating symbols...' . PHP_EOL;

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

		try {
			// Parse the PHP file
			$contents = file_get_contents( $file );

			if ( $contents === false ) {
				throw new \Exception( 'Failed to read file ' . $file );
			}

			$stmts = $parser->parse( $contents );

			if ( ! is_array( $stmts ) ) {
				throw new \Exception( 'Failed to parse file ' . $file );
			}

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

				if ( $function instanceof Node\Stmt\ClassMethod ) {
					$class = $function->getAttribute( 'parent' );
					if ( ( $class instanceof Node\Stmt\Class_ ) && ( $class->name instanceof Node\Identifier ) ) {
						$function_name = $class->name->toString() . '::' . $function_name;
					}
				}

				if ( $function_name === 'wp_handle_upload_error' ) {
					continue;
				}

				if ( str_starts_with( $function_name, 'WP_Internal_Pointers::pointer_wp' ) ) {
					continue;
				}

				if ( $doc_comment instanceof Doc ) {
					$comment_text = $doc_comment->getText();
					if ( preg_match( '/@since\s+([\w.-]+)/', $comment_text, $matches ) === 1 ) {
						$since = $matches[1];

						if ( $since === 'MU' ) {
							$since = '3.0.0';
						}

						if ( preg_match( '/^\d+\.\d+(\.\d+)?$/', $since ) !== 1 ) {
							$message = sprintf(
								'Invalid @since value of "%s" for %s() in %s:%d',
								$since,
								$function_name,
								$file_path,
								$function->getStartLine(),
							);

							throw new \Exception( $message );
						}

						$results[ $function_name ] = array(
							'since' => $since,
							// 'file' => $relative_path,
						);
					} else {
						$message = sprintf(
							'@since tag missing for %s() in %s:%d',
							$function_name,
							$file_path,
							$function->getStartLine(),
						);

						// echo $message . PHP_EOL;
					}
				} else {
					$message = sprintf(
						'Doc comment missing for %s() in %s:%d',
						$function_name,
						$file_path,
						$function->getStartLine(),
					);

					// echo $message . PHP_EOL;
				}
			}
		} catch ( Error $e ) {
			// Handle parsing errors
			echo 'Error parsing file: ', $e->getMessage();
			exit( 1 );
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

file_put_contents( $output_file, $json );

echo 'Done.' . PHP_EOL;
