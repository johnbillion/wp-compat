<?php

declare(strict_types=1);

namespace WPCompat\PHPStan\Tests;

use PHPUnit\Framework\TestCase;
use WPCompat\PHPStan\Generator\SymbolExtractor;

class SymbolExtractorTest extends TestCase {
	/**
	 * @param array<string, array{deprecated?: string, since: string, parameters?: array<string, array{since: string}>}> $expected
	 *
	 * @dataProvider dataSymbols
	 */
	public function testSymbolsAreExtracted( string $file, array $expected ): void {
		$path = __DIR__ . '/data/symbols/' . $file;
		$code = file_get_contents( $path );

		self::assertIsString( $code );

		$extractor = new SymbolExtractor();

		self::assertSame( $expected, $extractor->extract( $code, $path ) );
		self::assertSame( [], $extractor->getNotices() );
	}

	/**
	 * @phpstan-return array<string, array{
	 *   string,
	 *   array<string, array{deprecated?: string, since: string, parameters?: array<string, array{since: string}>}>,
	 * }>
	 */
	public function dataSymbols(): array {
		return [
			'a function with a @since tag' => [
				'basic-function.php',
				[
					'wpcompat_test_basic_function' => [
						'since' => '1.2.0',
					],
				],
			],
			'a function with parameters added over time' => [
				'basic-function-with-parameters.php',
				[
					'wpcompat_test_function_with_parameters' => [
						'since' => '1.2.0',
						'parameters' => [
							'extra' => [
								'since' => '2.0.0',
							],
							'more' => [
								'since' => '1.3.0',
							],
						],
					],
				],
			],
			// The later @since tags describe args added to the `$args` array, not the addition
			// of the `$args` parameter itself. Its introduction in 2.8.0 (as `$in_footer`) isn't
			// documented, so no parameter data should be recorded.
			'a function whose @since tags describe additions to an array parameter' => [
				'wp-enqueue-script.php',
				[
					'wp_enqueue_script' => [
						'since' => '2.1.0',
					],
				],
			],
			// "Array support was added to the `$query` parameter" describes a change to an
			// existing parameter, not its addition.
			'a function whose @since tag describes a change to a parameter' => [
				'add-rewrite-rule.php',
				[
					'add_rewrite_rule' => [
						'since' => '2.1.0',
					],
				],
			],
			// As above, for a method that inherits its @since from its class docblock.
			'a method whose @since tag describes an addition to a parameter' => [
				'wp-http-cookie.php',
				[
					'WP_Http_Cookie::__construct' => [
						'since' => '2.8.0',
					],
				],
			],
			// "Added 'ID' as an alias of 'id' for the `$field` parameter" describes a new
			// accepted value, not a new parameter.
			'a function whose @since tag describes a new accepted value' => [
				'get-user-by.php',
				[
					'get_user_by' => [
						'since' => '2.8.0',
					],
				],
			],
			// `$post_type` was added in 4.7.0, `$post` was not.
			'a function with a parameter whose name is a prefix of another' => [
				'get-page-templates.php',
				[
					'get_page_templates' => [
						'since' => '1.5.0',
						'parameters' => [
							'post_type' => [
								'since' => '4.7.0',
							],
						],
					],
				],
			],
			// As above, where the prefixed parameter has genuine additions of its own.
			'a function with several parameters added over time' => [
				'recurse-dirsize.php',
				[
					'recurse_dirsize' => [
						'since' => '3.0.0',
						'parameters' => [
							'directory_cache' => [
								'since' => '5.6.0',
							],
							'exclude' => [
								'since' => '4.3.0',
							],
							'max_execution_time' => [
								'since' => '5.2.0',
							],
						],
					],
				],
			],
			// `$filesize` is a value in the returned array, not the `$file` parameter.
			'a function whose @since tag describes a change to its return value' => [
				'wp-generate-attachment-metadata.php',
				[
					'wp_generate_attachment_metadata' => [
						'since' => '2.1.0',
					],
				],
			],
			// The first sentence mentions `$comment` and the second mentions an argument, but
			// neither says that `$comment` was added.
			'a function whose @since tag contains two sentences' => [
				'get-comment-link.php',
				[
					'get_comment_link' => [
						'since' => '1.5.0',
					],
				],
			],
		];
	}
}
