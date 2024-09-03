<?php

declare(strict_types=1);

namespace WPCompat\PHPStan\Tests;

use WPCompat\PHPStan\Rules\SinceVersionRule;

/**
 * @extends \PHPStan\Testing\RuleTestCase<\WPCompat\Rules\SinceVersionRule>
 */
class SinceVersionRuleTest extends \PHPStan\Testing\RuleTestCase {
	protected function getRule(): \PHPStan\Rules\Rule {
		return new SinceVersionRule();
	}

	public function testRule(): void{
		$this->analyse(
			[
				__DIR__ . '/data/SinceVersionRule.php',
			],
			[
				// @TODO
			]
		);
	}
}
