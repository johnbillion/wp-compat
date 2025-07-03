<?php

declare(strict_types=1);

namespace WPCompat\PHPStan\Tests;

use WPCompat\PHPStan\Rules\SinceVersionRule;

/**
 * @extends \PHPStan\Testing\RuleTestCase<\WPCompat\PHPStan\Rules\SinceVersionRule>
 */
class SinceVersionRuleTest extends \PHPStan\Testing\RuleTestCase {
	protected function getRule(): \PHPStan\Rules\Rule {
		return new SinceVersionRule(
			'6.0',
			null,
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void {
		$this->analyse(
			[
				__DIR__ . '/data/SinceVersion.php',
			],
			[
				[
					'WP_Date_Query::sanitize_relation() is only available since WordPress version 6.0.3.',
					9,
				],
				[
					'WP_Object_Cache::flush_group() is only available since WordPress version 6.1.0.',
					13,
				],
				[
					'WP_Object_Cache::flush_group() is only available since WordPress version 6.1.0.',
					18,
				],
				[
					'get_template_hierarchy() is only available since WordPress version 6.1.0.',
					22,
				],
				[
					'WP_Date_Query::sanitize_relation() is only available since WordPress version 6.0.3.',
					28,
				],
				[
					'WP_Block_Bindings_Registry::get_instance() is only available since WordPress version 6.5.0.',
					31,
				],
				[
					'get_template_hierarchy() is only available since WordPress version 6.1.0.',
					37,
				],
				[
					'WP_Date_Query::sanitize_relation() is only available since WordPress version 6.0.3.',
					42,
				],
				[
					'Filter ajax_term_search_results is only available since WordPress version 6.1.0.',
					49,
				],
				[
					'Action wp_cache_set_last_changed is only available since WordPress version 6.3.0.',
					52,
				],
			],
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
