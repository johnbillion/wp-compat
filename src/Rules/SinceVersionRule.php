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
	private static string $functionIdentifier = 'WPCompat.functionNotAvailable';
	private static string $methodIdentifier = 'WPCompat.methodNotAvailable';

	/**
	 * @var array<string, array{since: string}>
	 */
	private array $symbols;

	private string $minVersion;

	private ReflectionProvider $reflectionProvider;

	public function __construct(
		string $requiresAtLeast,
		ReflectionProvider $reflectionProvider
	) {
		$symbolsFilePath = dirname( __DIR__, 2 ) . '/symbols.json';
		$contents = file_get_contents( $symbolsFilePath );

		if ( $contents === false ) {
			throw new \RuntimeException( 'Failed to read symbols.json' );
		}

		$this->minVersion = $requiresAtLeast;
		$this->symbols = json_decode( $contents, true )['symbols'];
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

		if ( $scope->isInFunctionExists( $name ) ) {
			return [];
		}

		if ( ! isset( $this->symbols[ $name ] ) ) {
			return [];
		}

		$since = $this->symbols[ $name ]['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return [];
		}

		$message = sprintf(
			'%s() is only available since %s version %s.',
			$name,
			'WordPress',
			$since,
		);

		return [
			RuleErrorBuilder::message( $message )->identifier( self::$functionIdentifier )->build(),
		];
	}

	private function isInMethodExists( CallLike $node, Scope $scope ): bool {
		if ( ! $node instanceof MethodCall ) {
			return false;
		}

		$methodName = self::getMethodName( $node );

		$inMethodExists = $node->getAttribute( MethodExistsVisitor::ATTRIBUTE_NAME );
		foreach ( $inMethodExists as [$objectOrClass, $method] ) {
			if ( $methodName !== $method->value ) {
				continue;
			}

			if (
				$objectOrClass instanceof Node\Expr\Variable
				&& $node->var instanceof Node\Expr\Variable
			) {
				if ( $node->var->name === $objectOrClass->name ) {
					return true;
				}
			}

			$classNames = $scope->getType( $node->var )->getObjectClassNames();
			if (
				$objectOrClass instanceof Node\Scalar\String_
				&& in_array( $objectOrClass->value, $classNames, true )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param MethodCall|StaticCall $node
	 * @return list<RuleError>
	 */
	private function processMethodCall( CallLike $node, Scope $scope ): array {
		if ( $node instanceof MethodCall ) {
			$methodCalledOnType = $scope->getType( $node->var );
			$classNames = $methodCalledOnType->getObjectClassNames();
		} elseif ( $node->class instanceof Name ) {
			$classNames = [
				$node->class->toString(),
			];
		} else {
			return [];
		}

		if ( $this->isInMethodExists( $node, $scope ) ) {
			return [];
		}

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

		$methodName = self::getMethodName( $node );
		foreach ( $allClassNames as $className ) {
			$name = sprintf(
				'%s::%s',
				$className,
				$methodName,
			);

			if ( isset( $this->symbols[ $name ] ) ) {
				return $this->processMethodVersion( $name, $this->symbols[ $name ] );
			}
		}

		return [];
	}

	/**
	 * @param array{since: string} $symbol
	 * @return list<RuleError>
	 */
	private function processMethodVersion( string $name, array $symbol ): array {
		$since = $symbol['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return [];
		}

		$message = sprintf(
			'%s() is only available since %s version %s.',
			$name,
			'WordPress',
			$since,
		);

		return [
			RuleErrorBuilder::message( $message )->identifier( self::$methodIdentifier )->build(),
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
