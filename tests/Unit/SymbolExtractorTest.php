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
		];
	}
}
