<?php declare(strict_types=1);

namespace WPCompat\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\CallLike>
 */
class SinceVersionRule implements Rule {
	/**
	 * @var array<string, array{since: string}>
	 */
	private array $symbols;

	private string $minVersion;

	public function __construct() {
		$symbolsFilePath = dirname( __DIR__, 2 ) . '/symbols.json';
		$contents = file_get_contents( $symbolsFilePath );

		if ( $contents === false ) {
			throw new \RuntimeException( 'Failed to read symbols.json' );
		}

		$this->symbols = json_decode( $contents, true )['symbols'];
		$this->minVersion = '6.0';
	}

	public function getNodeType(): string {
		return CallLike::class;
	}

	/**
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
