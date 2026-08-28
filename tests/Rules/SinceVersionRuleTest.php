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
			self::createReflectionProvider()
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
				[
					'Parameter $locale of load_textdomain() is only available since WordPress version 6.1.0.',
					55,
					null,
					'WPCompat.parameterNotAvailable.loadtextdomain.locale',
				],
				[
					'Parameter $valid_variations of WP_Theme_JSON::sanitize() is only available since WordPress version 6.3.0.',
					59,
					null,
					'WPCompat.parameterNotAvailable.WPThemeJSONsanitize.validvariations',
				],
				[
					'Parameter $locale of load_textdomain() is only available since WordPress version 6.1.0.',
					62,
					null,
					'WPCompat.parameterNotAvailable.loadtextdomain.locale',
				],
				[
					'Parameter $previous_status of filter pre_trash_post is only available since WordPress version 6.3.0.',
					65,
					null,
					'WPCompat.parameterNotAvailable.filter.pretrashpost.previousstatus',
				],
				[
					'Parameter $post_id of action _wp_put_post_revision is only available since WordPress version 6.4.0.',
					68,
					null,
					'WPCompat.parameterNotAvailable.action.wpputpostrevision.postid',
				],
				[
					"Key \$args['label'] of register_meta() is only available since WordPress version 6.7.0.",
					71,
					null,
					'WPCompat.parameterKeyNotAvailable.registermeta.args.label',
				],
				[
					"Key \$attr['decoding'] of wp_get_attachment_image() is only available since WordPress version 6.1.0.",
					74,
					null,
					'WPCompat.parameterKeyNotAvailable.wpgetattachmentimage.attr.decoding',
				],
				[
					"Key \$options['skip_root_layout_styles'] of WP_Theme_JSON::get_stylesheet() is only available since WordPress version 6.6.0.",
					78,
					null,
					'WPCompat.parameterKeyNotAvailable.WPThemeJSONgetstylesheet.options.skiprootlayoutstyles',
				],
				[
					"Key \$args['label'] of register_meta() is only available since WordPress version 6.7.0.",
					83,
					null,
					'WPCompat.parameterKeyNotAvailable.registermeta.args.label',
				],
			]
		);
	}

	public function testRuleErrorIdentifiers(): void {
		$errors = $this->gatherAnalyserErrors( [ __DIR__ . '/data/SinceVersion.php' ] );
		$identifiers = array_map(
			static function ( \PHPStan\Analyser\Error $error ): ?string {
				return $error->getIdentifier();
			},
			$errors
		);

		$this->assertContains( 'WPCompat.parameterNotAvailable.loadtextdomain.locale', $identifiers );
		$this->assertContains( 'WPCompat.parameterNotAvailable.WPThemeJSONsanitize.validvariations', $identifiers );
		$this->assertContains( 'WPCompat.parameterNotAvailable.filter.pretrashpost.previousstatus', $identifiers );
		$this->assertContains( 'WPCompat.parameterNotAvailable.action.wpputpostrevision.postid', $identifiers );
		$this->assertContains( 'WPCompat.parameterKeyNotAvailable.registermeta.args.label', $identifiers );
		$this->assertContains( 'WPCompat.parameterKeyNotAvailable.WPThemeJSONgetstylesheet.options.skiprootlayoutstyles', $identifiers );
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
