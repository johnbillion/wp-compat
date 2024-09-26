<?php

declare(strict_types=1);

namespace WPCompat\PHPStan\Tests;

use WPCompat\PHPStan\Rules\SinceVersionRule;

/**
 * @extends \PHPStan\Testing\RuleTestCase<\WPCompat\PHPStan\Rules\SinceVersionRule>
 */
class CompatTest extends \PHPStan\Testing\RuleTestCase {
	protected function getRule(): \PHPStan\Rules\Rule {
		return new SinceVersionRule(
			'1.0',
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void {
		$this->analyse(
			[
				__DIR__ . '/data/Compat.php',
			],
			[],
		);
	}

	/**
	 * @return list<string>
	 */
	public static function getAdditionalConfigFiles(): array {
		return [
			dirname( __DIR__ ) . '/tests.neon',
		];
	}
}
