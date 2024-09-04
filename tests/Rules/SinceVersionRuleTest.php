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
				[
					'WP_Date_Query::sanitize_relation() is only available since version 6.0.3.',
					9,
				],
				[
					'WP_Object_Cache::flush_group() is only available since version 6.1.0.',
					13,
				],
				[
					'WP_Object_Cache::flush_group() is only available since version 6.1.0.',
					18,
				],
				[
					'get_template_hierarchy() is only available since version 6.1.0.',
					22,
				],
				[
					'WP_Date_Query::sanitize_relation() is only available since version 6.0.3.',
					27,
				],
			],
		);
	}
}
