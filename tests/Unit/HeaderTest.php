<?php

declare(strict_types=1);

namespace WPCompat\PHPStan\Tests;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use WPCompat\PHPStan\Rules\SinceVersionRule;

class HeaderTest extends PHPStanTestCase {
	private static ReflectionProvider $reflectionProvider;

	public static function setUpBeforeClass(): void {
		self::$reflectionProvider = self::createReflectionProvider();
	}

	/**
	 * @dataProvider dataMinimumVersions
	 */
	public function testMinimumVersionIsCorrect( ?string $requiresAtLeast, ?string $pluginFile, string $expected ): void {
		$since = new SinceVersionRule(
			$requiresAtLeast,
			$pluginFile,
			self::$reflectionProvider,
		);

		self::assertSame( $expected, $since->getMinVersion() );
	}

	/**
	 * @phpstan-return list<array{
	 *   ?string,
	 *   ?string,
	 *   string,
	 * }>
	 */
	public function dataMinimumVersions(): array {
		return [
			[
				'1.2',
				null,
				'1.2.0',
			],
			[
				'1.2.3',
				null,
				'1.2.3',
			],
			[
				null,
				'tests/Unit/data/plugin1.php',
				'5.0.0',
			],
		];
	}
}
