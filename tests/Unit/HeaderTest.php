<?php

declare(strict_types=1);

namespace WPCompat\PHPStan\Tests;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use WPCompat\PHPStan\Rules\SinceVersionRule;

class HeaderTest extends PHPStanTestCase {
	/**
	 * @var ReflectionProvider
	 */
	private static $reflectionProvider;

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
			self::$reflectionProvider
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

	public function testPluginFileIsFoundWhenDirectoryNameDoesNotMatch(): void {
		$since = $this->createRuleInDirectory( __DIR__ . '/data/worktree' );

		self::assertSame( '5.5.0', $since->getMinVersion() );
	}

	public function testMissingPluginFileThrowsHelpfulException(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Set the WPCompat.pluginFile or WPCompat.requiresAtLeast parameter' );

		$this->createRuleInDirectory( __DIR__ . '/data/no-plugin' );
	}

	public function testMissingConfiguredPluginFileThrowsHelpfulException(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'The plugin file "does-not-exist.php" set in the WPCompat.pluginFile parameter was not found' );

		new SinceVersionRule(
			null,
			'does-not-exist.php',
			self::$reflectionProvider
		);
	}

	private function createRuleInDirectory( string $dir ): SinceVersionRule {
		$cwd = (string) getcwd();
		chdir( $dir );

		try {
			return new SinceVersionRule(
				null,
				null,
				self::$reflectionProvider
			);
		} finally {
			chdir( $cwd );
		}
	}
}
