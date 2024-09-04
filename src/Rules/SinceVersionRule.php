<?php declare(strict_types=1);

namespace WPCompat\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Analyser\Scope;

class SinceVersionRule implements Rule {
	private array $symbols;

	private string $minVersion;

	public function __construct() {
		$symbolsFilePath = dirname( __DIR__, 2 ) . '/symbols.json';
		$this->symbols = json_decode( file_get_contents( $symbolsFilePath ), true )['symbols'];
		$this->minVersion = '6.0';
	}

	public function getNodeType(): string {
		return CallLike::class;
	}

	/**
	 * @param CallLike $node
	 * @return list<RuleError>
	 */
	public function processNode( Node $node, Scope $scope ): array {
		if ( ( ! $node instanceof FuncCall ) && ( ! $node instanceof MethodCall ) && ( ! $node instanceof StaticCall ) ) {
			return [];
		}

		$name = self::getNodeName( $node );

		if ( ! is_string( $name ) || ! isset( $this->symbols[ $name ] ) ) {
			return [];
		}

		$since = $this->symbols[ $name ]['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return [];
		}

		$message = sprintf(
			'%s() is only available since version %s.',
			$name,
			$since,
		);

		return [
			RuleErrorBuilder::message( $message )->identifier( 'WPCompat.nope' )->build(),
		];
	}

	private static function getNodeName( Node $node ): ?string {
		if ( $node instanceof FuncCall ) {
			return (string) $node->name;
		}

		if ( $node instanceof MethodCall ) {
			return (string) $node->name;
		}

		if ( $node instanceof StaticCall ) {
			return (string) $node->name;
		}

		return null;
	}
}
