<?php declare(strict_types=1);

namespace WPCompat\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\CallLike>
 */
final class SinceVersionRule implements Rule {
	/**
	 * @var array<string, array{since: string}>
	 */
	private array $symbols;

	private string $minVersion;

	private ReflectionProvider $reflectionProvider;

	public function __construct( ReflectionProvider $reflectionProvider ) {
		$symbolsFilePath = dirname( __DIR__, 2 ) . '/symbols.json';
		$contents = file_get_contents( $symbolsFilePath );

		if ( $contents === false ) {
			throw new \RuntimeException( 'Failed to read symbols.json' );
		}

		$this->symbols = json_decode( $contents, true )['symbols'];
		$this->minVersion = '6.0.0';
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string {
		return CallLike::class;
	}

	/**
	 * @return list<RuleError>
	 */
	public function processNode( Node $node, Scope $scope ): array {
		if ( $node instanceof FuncCall ) {
			return $this->processFuncCall( $node, $scope );
		}

		// @TODO null-safe method calls
		if ( ( $node instanceof MethodCall ) || ( $node instanceof StaticCall ) ) {
			return $this->processMethodCall( $node, $scope );
		}

		return [];
	}

	/**
	 * @return list<RuleError>
	 */
	private function processFuncCall( FuncCall $node, Scope $scope ): array {
		$name = self::getFunctionName( $node );

		if ( ! isset( $this->symbols[ $name ] ) ) {
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

	/**
	 * @param MethodCall|StaticCall $node
	 * @return list<RuleError>
	 */
	private function processMethodCall( CallLike $node, Scope $scope ): array {
		$methodCalledOnType = $scope->getType( $node->var );
		$classNames = $methodCalledOnType->getObjectClassNames();
		$allClassNames = $classNames;

		// determine the names of all the classes that this class extends from:
		foreach ( $classNames as $className ) {
			try {
				$classReflection = $this->reflectionProvider->getClass( $className );
			} catch ( ClassNotFoundException $e ) {
				// ?
				continue;
			}

			$allClassNames = array_merge( $allClassNames, $classReflection->getParentClassesNames() );
		}

		// var_dump( '$allClassNames', $allClassNames );

		foreach ( $allClassNames as $className ) {
			$name = sprintf(
				'%s::%s',
				$className,
				self::getMethodName( $node ),
			);

			if ( isset( $this->symbols[ $name ] ) ) {
				return $this->processBoop( $name, $this->symbols[ $name ] );
			}
		}

		return [];
	}

	private function processBoop( string $name, array $symbol ): array {
		$since = $symbol['since'];

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

	/**
	 * @throws \RuntimeException
	 */
	private static function getFunctionName( FuncCall $node ): string {
		if ( $node->name instanceof Name ) {
			return $node->name->toString();
		}

		throw new \RuntimeException( 'Failed to get function name' );
	}

	/**
	 * @param MethodCall|StaticCall $node
	 * @throws \RuntimeException
	 */
	private static function getMethodName( CallLike $node ): string {
		if ( ( $node instanceof MethodCall ) && ( $node->name instanceof Identifier ) ) {
			return $node->name->toString();
		}

		if ( ( $node instanceof StaticCall ) && ( $node->name instanceof Identifier ) ) {
			return $node->name->toString();
		}

		throw new \RuntimeException( 'Failed to get method name' );
	}
}
