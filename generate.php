<?php
#!/usr/bin/env php

namespace WPCompat\PHPStan;

use PhpParser\Error;
use WPCompat\PHPStan\Generator\SymbolExtractor;

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

$extractor = new SymbolExtractor();

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

		try {
			$results = array_merge( $results, $extractor->extract( $contents, $file_path ) );
		} catch ( Error $e ) {
			// Handle parsing errors
			throw new \Exception( 'Error parsing file: ' . $e->getMessage() );
		}

		foreach ( $extractor->getNotices() as $notice ) {
			echo $notice . PHP_EOL;
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
