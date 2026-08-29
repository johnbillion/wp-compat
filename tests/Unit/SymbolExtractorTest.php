<?php

declare(strict_types=1);

namespace WPCompat\PHPStan\Tests;

use PHPUnit\Framework\TestCase;
use WPCompat\PHPStan\Generator\SymbolExtractor;

class SymbolExtractorTest extends TestCase {
	/**
	 * @param array<string, array{deprecated?: string, since: string, parameters?: array<string, array{since?: string, keys?: array<string, array{since: string}>}>}> $expected
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
	 *   array<string, array{deprecated?: string, since: string, parameters?: array<string, array{since?: string, keys?: array<string, array{since: string}>}>}>,
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
			// of the `$args` parameter itself, so it's recorded with its keys but without a
			// version of its own. The 6.3.0 entry describes `$in_footer` being overloaded into
			// `$args` rather than an argument being added, so neither the `in_footer` nor the
			// `strategy` key is recorded, even though both only work as keys from 6.3.0.
			'a function whose @since tags describe additions to an array parameter' => [
				'wp-enqueue-script.php',
				[
					'wp_enqueue_script' => [
						'since' => '2.1.0',
						'parameters' => [
							'args' => [
								'keys' => [
									'fetchpriority' => [
										'since' => '6.9.0',
									],
									'module_dependencies' => [
										'since' => '7.0.0',
									],
								],
							],
						],
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
			// As above, for a method that inherits its @since from its class docblock. What was
			// added is the `host_only` key of `$data`.
			'a method whose @since tag describes an addition to a parameter' => [
				'wp-http-cookie.php',
				[
					'WP_Http_Cookie::__construct' => [
						'since' => '2.8.0',
						'parameters' => [
							'data' => [
								'keys' => [
									'host_only' => [
										'since' => '5.2.0',
									],
								],
							],
						],
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
			// neither says that `$comment` was added. The `$cpage` argument of the second
			// sentence is a key of `$args`.
			'a function whose @since tag contains two sentences' => [
				'get-comment-link.php',
				[
					'get_comment_link' => [
						'since' => '1.5.0',
						'parameters' => [
							'args' => [
								'keys' => [
									'cpage' => [
										'since' => '4.4.0',
									],
								],
							],
						],
					],
				],
			],
			// Each of these attributes is a key of the `$attr` parameter, which itself has no
			// documented introduction. The `fetchpriority` key has no @since entry of its own
			// in WordPress, so it isn't recorded either.
			'a function whose @since tags describe additions to its array of attributes' => [
				'wp-get-attachment-image.php',
				[
					'wp_get_attachment_image' => [
						'since' => '2.5.0',
						'parameters' => [
							'attr' => [
								'keys' => [
									'decoding' => [
										'since' => '6.1.0',
									],
									'loading' => [
										'since' => '5.5.0',
									],
									'sizes' => [
										'since' => '4.4.0',
									],
									'srcset' => [
										'since' => '4.4.0',
									],
								],
							],
						],
					],
				],
			],
			// The `$default` and `$label` keys are referred to in their dollar prefixed and bare
			// backticked forms respectively.
			'a function with several array keys added over time' => [
				'register-meta.php',
				[
					'register_meta' => [
						'since' => '3.3.0',
						'parameters' => [
							'args' => [
								'keys' => [
									'default' => [
										'since' => '5.5.0',
									],
									'label' => [
										'since' => '6.7.0',
									],
									'object_subtype' => [
										'since' => '4.9.8',
									],
									'revisions_enabled' => [
										'since' => '6.4.0',
									],
								],
							],
						],
					],
				],
			],
			// `$flavour` arrived with the `$args` parameter that holds it, so only `$topping` is
			// worth recording as a key.
			'a function with an array parameter and a key added after it' => [
				'wpcompat-parameter-with-keys.php',
				[
					'wpcompat_test_parameter_with_keys' => [
						'since' => '1.0.0',
						'parameters' => [
							'args' => [
								'since' => '2.0.0',
								'keys' => [
									'topping' => [
										'since' => '3.0.0',
									],
								],
							],
						],
					],
				],
			],
			// `label` is documented as a key of both `$before` and `$after`, so the changelog
			// entry can't be attributed to either. `name` is documented once, so it can.
			'a function with a nested hash notation' => [
				'wpcompat-nested-hash.php',
				[
					'wpcompat_test_nested_hash' => [
						'since' => '1.0.0',
						'parameters' => [
							'args' => [
								'keys' => [
									'before.name' => [
										'since' => '2.0.0',
									],
								],
							],
						],
					],
				],
			],
			// A key which shares its name with a parameter is left to the parameter handling, and
			// support for an argument, an argument becoming optional, and a rename all describe
			// changes to an argument that already existed.
			'a function whose @since tags do not describe key additions' => [
				'wpcompat-key-non-additions.php',
				[
					'wpcompat_test_key_non_additions' => [
						'since' => '1.0.0',
						'parameters' => [
							'context' => [
								'since' => '2.0.0',
							],
						],
					],
				],
			],
			// `$sprinkles` is named on the second line of its entry, so the description has to be
			// joined back together before it can be seen. The `cone` and `order` keys aren't
			// added by their entries, they're deprecated and given a new syntax respectively.
			'a function whose @since descriptions span several lines' => [
				'wpcompat-multiline-since.php',
				[
					'wpcompat_test_multiline_since' => [
						'since' => '1.0.0',
						'parameters' => [
							'args' => [
								'keys' => [
									'flavour' => [
										'since' => '2.0.0',
									],
									'sauce' => [
										'since' => '3.0.0',
									],
									'sprinkles' => [
										'since' => '2.0.0',
									],
									'topping' => [
										'since' => '2.0.0',
									],
								],
							],
						],
					],
				],
			],
			// The 5.3.0 entry continues onto a second line with "which enables the `$key` to be
			// cast to a new data type". That names an existing key in passing rather than adding
			// it, so only `$type_key` is recorded.
			'a method whose @since description continues into a relative clause' => [
				'wp-meta-query.php',
				[
					'WP_Meta_Query::__construct' => [
						'since' => '3.2.0',
						'parameters' => [
							'meta_query' => [
								'keys' => [
									'compare_key' => [
										'since' => '5.1.0',
									],
									'type_key' => [
										'since' => '5.3.0',
									],
								],
							],
						],
					],
				],
			],
		];
	}
}
