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
if ( isset($argv[1]) ) {
	$directory = rtrim( $argv[1], '/' );
} else {
	echo 'No directory name provided. Exiting...' . PHP_EOL;
	exit(1);
}

// Output file path
$outputFile = __DIR__ . '/symbols.json';

// Create a new parser instance
$parser = (new ParserFactory())->createForNewestSupportedVersion();

// Initialize an array to store the results
$results = [];

// List of directories to exclude
// @TODO hardcode the @since versions for symbols in these files
$excludedPaths = [
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
];

echo 'Scanning and collating symbols...' . PHP_EOL;

// Iterate each PHP file in the directory
$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
foreach ($files as $file) {
	if ($file->isFile() && $file->getExtension() === 'php') {
		$filePath = $file->getPathname();
		$relativePath = str_replace($directory . '/', '', $filePath);

		// Check if the file is in an excluded directory
		foreach ($excludedPaths as $excludedPath) {
			if (str_starts_with($relativePath, $excludedPath)) {
				continue 2;
			}
		}

		try {
			// Parse the PHP file
			$stmts = $parser->parse(file_get_contents($file));

			// Find all function and method nodes
			// Create a new FindingVisitor instance
			$visitor = new FindingVisitor(function (Node $node) {
				return $node instanceof Node\Stmt\Function_
					|| $node instanceof Node\Stmt\ClassMethod;
			});

			// Traverse the AST and find all function and method nodes
			$traverser = new NodeTraverser();
			$traverser->addVisitor($visitor);
			$traverser->addVisitor(new ParentConnectingVisitor());
			$traverser->traverse($stmts);

			// Get the found functions and methods
			$functions = $visitor->getFoundNodes();

			// Extract the function and method names along with their @since values
			foreach ($functions as $function) {
				$docComment = $function->getDocComment();
				$functionName = $function->name->toString();

				if ($function instanceof Node\Stmt\ClassMethod) {
					$class = $function->getAttribute('parent');
					if ($class instanceof Node\Stmt\Class_) {
						$functionName = $class->name->toString() . '::' . $functionName;
					}
				}

				if ( $functionName === 'wp_handle_upload_error' ) {
					continue;
				}

				if ( str_starts_with($functionName, 'WP_Internal_Pointers::pointer_wp') ) {
					continue;
				}

				if ($docComment !== null) {
					if ($docComment instanceof Doc) {
						$commentText = $docComment->getText();
						if (preg_match('/@since\s+([\w.-]+)/', $commentText, $matches)) {
							$since = $matches[1];

							if ( $since === 'MU' ) {
								$since = '3.0.0';
							}

							if ( ! preg_match('/^\d+\.\d+(\.\d+)?$/', $since) ) {
								$message = sprintf(
									'Invalid @since value of "%s" for %s() in %s:%d',
									$since,
									$functionName,
									$filePath,
									$function->getStartLine(),
								);

								throw new \Exception( $message );
							}

							$results[ $functionName ] = [
								'since' => $since,
								// 'file' => $relativePath,
							];
						} else {
							$message = sprintf(
								'@since tag missing for %s() in %s:%d',
								$functionName,
								$filePath,
								$function->getStartLine(),
							);

							// echo $message . PHP_EOL;
						}
					} else {
						$message = sprintf(
							'Invalid doc comment for %s() in %s:%d',
							$functionName,
							$filePath,
							$function->getStartLine(),
						);

						throw new \Exception( $message );
					}
				} else {
					// $message = sprintf(
					// 	'Doc comment missing for %s() in %s:%d',
					// 	$functionName,
					// 	$filePath,
					// 	$function->getStartLine(),
					// );

					// echo $message . PHP_EOL;
				}
			}
		} catch (Error $e) {
			// Handle parsing errors
			echo 'Error parsing file: ', $e->getMessage();
			exit(1);
		}
	}
}

ksort($results);

echo 'Scanning complete, writing data.' . PHP_EOL;

// Write the results to the output file
$data = [
	'$schema' => 'https://raw.githubusercontent.com/johnbillion/wp-compat/trunk/schemas/symbols.json',
	'symbols' => $results,
];
$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

file_put_contents($outputFile, $json);

echo 'Done.' . PHP_EOL;
