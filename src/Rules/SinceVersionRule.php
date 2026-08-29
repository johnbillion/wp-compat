<?php declare(strict_types=1);

namespace WPCompat\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use WPCompat\PHPStan\ParameterAdditionMatcher;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\CallLike>
 */
final class SinceVersionRule implements Rule {
	/**
	 * @var string
	 */
	private static $functionIdentifier = 'WPCompat.functionNotAvailable';

	/**
	 * @var string
	 */
	private static $methodIdentifier = 'WPCompat.methodNotAvailable';

	/**
	 * @var string
	 */
	private static $filterIdentifier = 'WPCompat.filterNotAvailable';

	/**
	 * @var string
	 */
	private static $actionIdentifier = 'WPCompat.actionNotAvailable';

	/**
	 * @var string
	 */
	private static $parameterIdentifier = 'WPCompat.parameterNotAvailable';

	/**
	 * @var string
	 */
	private static $errorIdentifier = 'WPCompat.error';

	/**
	 * @var array<string, array{since: string, parameters?: array<string, array{since: string}>}>
	 */
	private $symbols;

	/**
	 * @var array<string, array{since: string, parameters?: array<int, array{name: string, since: string}>}>
	 */
	private $filters = [];

	/**
	 * @var array<string, array{since: string, parameters?: array<int, array{name: string, since: string}>}>
	 */
	private $actions = [];

	/**
	 * @var string
	 */
	private $minVersion;

	/**
	 * @var ReflectionProvider
	 */
	private $reflectionProvider;

	public function __construct(
		?string $requiresAtLeast,
		?string $pluginFile,
		ReflectionProvider $reflectionProvider
	) {
		$symbolsFilePath = dirname( __DIR__, 2 ) . '/symbols.json';
		$contents = file_get_contents( $symbolsFilePath );

		if ( $contents === false ) {
			throw new \RuntimeException( 'Failed to read symbols.json' );
		}

		$this->filters = $this->loadHooksData( 'filters' );
		$this->actions = $this->loadHooksData( 'actions' );

		$minVersion = $requiresAtLeast ?? self::getRequiresAtLeastValue( $pluginFile );

		$this->minVersion = self::normaliseVersion( $minVersion );
		$this->symbols = json_decode( $contents, true )['symbols'];
		$this->reflectionProvider = $reflectionProvider;
	}

	/**
	 * @param string $type
	 * @return array<string, array{since: string, parameters?: array<int, array{name: string, since: string}>}>
	 */
	private function loadHooksData( string $type ): array {
		$path = \Composer\InstalledVersions::getInstallPath( 'wp-hooks/wordpress-core' );

		if ( ! is_string( $path ) ) {
			throw new \RuntimeException( 'Failed to get install path for wp-hooks/wordpress-core' );
		}

		$filename = $path . '/hooks/' . $type . '.json';
		$contents = file_get_contents( $filename );

		if ( $contents === false ) {
			throw new \RuntimeException( "Failed to read {$type}.json" );
		}

		$data = json_decode( $contents, true );

		if ( ! is_array( $data ) ) {
			throw new \RuntimeException( "Failed to decode {$type}.json" );
		}

		$hooks = [];
		foreach ( $data['hooks'] as $hook ) {
			if ( ! isset( $hook['doc']['tags'] ) || ! is_array( $hook['doc']['tags'] ) ) {
				continue;
			}

			$sinceTags = array_filter(
				$hook['doc']['tags'],
				function ( array $tag ): bool {
					return isset( $tag['name'], $tag['content'] ) && $tag['name'] === 'since';
				}
			);
			$sinceTags = array_values( $sinceTags );

			if ( count( $sinceTags ) === 0 ) {
				continue;
			}

			$hookSince = $sinceTags[0]['content'];
			$hookData = [ 'since' => $hookSince ];

			$sinceTagsCount = count( $sinceTags );
			if ( $sinceTagsCount > 1 ) {
				$paramTags = array_filter(
					$hook['doc']['tags'],
					function ( array $tag ): bool {
						return isset( $tag['name'] ) && $tag['name'] === 'param';
					}
				);
				$paramTags = array_values( $paramTags );

				$paramVars = [];
				foreach ( $paramTags as $idx => $ptag ) {
					$var = $ptag['variable'] ?? '';
					if ( is_string( $var ) && $var !== '' ) {
						$paramVars[ $idx + 1 ] = ltrim( $var, '$' );
					}
				}

				$parameters = [];
				for ( $i = 1; $i < $sinceTagsCount; $i++ ) {
					$ver = $sinceTags[ $i ]['content'];
					$desc = $sinceTags[ $i ]['description'] ?? '';

					if ( ! is_string( $desc ) || $desc === '' || version_compare( $ver, $hookSince, '<=' ) ) {
						continue;
					}

					foreach ( $paramVars as $pos => $pvar ) {
						if ( ! ParameterAdditionMatcher::matches( $desc, $pvar ) ) {
							continue;
						}

						if ( ! isset( $parameters[ $pos ] ) || version_compare( $ver, $parameters[ $pos ]['since'], '<' ) ) {
							$parameters[ $pos ] = [
								'name'  => $pvar,
								'since' => $ver,
							];
						}
					}
				}

				if ( $parameters !== [] ) {
					$hookData['parameters'] = $parameters;
				}
			}

			$hooks[ $hook['name'] ] = $hookData;
		}

		return $hooks;
	}

	private static function getRequiresAtLeastValue( ?string $pluginFile ): string {
		$files = [];
		$cwd = getcwd();

		if ( is_string( $pluginFile ) ) {
			$files[] = $pluginFile;
		} elseif ( is_string( $cwd ) ) {
			$base = basename( $cwd );
			$files[] = "{$cwd}/{$base}.php";
			$files[] = "{$cwd}/plugin.php";
			$files[] = "{$cwd}/style.css";
		}

		foreach ( $files as $file ) {
			$path = self::realPath( $file );

			if ( ! is_string( $path ) || ! file_exists( $path ) ) {
				continue;
			}

			return self::getRequiresAtLeastHeader( $path );
		}

		throw new \RuntimeException( 'No plugin or theme file found' );
	}

	/**
	 * The logic in this method matches the logic in WordPress core's get_plugin_data() function.
	 */
	private static function getRequiresAtLeastHeader( string $pluginFile ): string {
		$contents = file_get_contents( $pluginFile );

		if ( ! is_string( $contents ) ) {
			throw new \RuntimeException(
				sprintf(
					'Failed to read file %s',
					$pluginFile
				)
			);
		}

		// Pull only the first 8 KB of the file in.
		$file_data = substr( $contents, 0, 8 * 1024 );

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );

		// Look for the Requires at least: line.
		$matched = preg_match( '/^[ \t\/*#@]*Requires at least:(.*)$/mi', $file_data, $match );

		if ( $matched === 1 && $match[1] !== '' ) {
			return (string) preg_replace( '#[^0-9\.]#', '', $match[1] );
		}

		throw new \RuntimeException(
			sprintf(
				'Could not read "Requires at least" value from file %s',
				$pluginFile
			)
		);
	}

	/**
	 * @return string|false
	 */
	private static function realPath( string $file ) {
		$path = realpath( $file );

		if ( is_string( $path ) ) {
			return $path;
		}

		return realpath( getcwd() . DIRECTORY_SEPARATOR . $file );
	}

	private static function normaliseVersion( string $minVersion ): string {
		// Convert a major.minor or major.minor.patch string to a major.minor.patch string:
		$parts = explode( '.', $minVersion );

		if ( count( $parts ) === 2 ) {
			$parts[] = '0';
		}

		return implode( '.', $parts );
	}

	public function getNodeType(): string {
		return CallLike::class;
	}

	/**
	 * @return list<IdentifierRuleError>
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

	private static function sanitizeIdentifier( string $name ): string {
		$result = preg_replace( '/[^a-zA-Z0-9.]/', '', $name );
		$result = $result ?? 'unknown';

		return $result;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processFuncCall( FuncCall $node, Scope $scope ): array {
		try {
			$name = self::getFunctionName( $node );
		} catch ( \RuntimeException $e ) {
			return [
				RuleErrorBuilder::message( $e->getMessage() )->identifier( self::$errorIdentifier )->build(),
			];
		}

		if ( ! is_string( $name ) ) {
			return [];
		}

		if ( 'add_filter' === $name ) {
			return $this->processFilterCall( $node );
		}

		if ( 'add_action' === $name ) {
			return $this->processActionCall( $node );
		}

		if ( $scope->isInFunctionExists( $name ) ) {
			return [];
		}

		if ( ! isset( $this->symbols[ $name ] ) ) {
			return [];
		}

		$since = $this->symbols[ $name ]['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return $this->processParameterVersions( $name, $this->symbols[ $name ], $node, $scope );
		}

		$message = sprintf(
			'%s() is only available since %s version %s.',
			$name,
			'WordPress',
			$since
		);

		return [
			RuleErrorBuilder::message( $message )->identifier( self::$functionIdentifier )->build(),
		];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processFilterCall( FuncCall $node ): array {
		if ( ! isset( $node->args[0] ) || ! $node->args[0] instanceof Arg || ! $node->args[0]->value instanceof String_ ) {
			return [];
		}

		$filterName = $node->args[0]->value->value;

		if ( ! isset( $this->filters[ $filterName ] ) ) {
			return [];
		}

		$since = $this->filters[ $filterName ]['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return $this->processHookParameters( 'Filter', $filterName, $this->filters[ $filterName ], $node );
		}

		$message = sprintf(
			'Filter %s is only available since %s version %s.',
			$filterName,
			'WordPress',
			$since
		);

		$sanitizedFilterName = self::sanitizeIdentifier( $filterName );
		return [
			RuleErrorBuilder::message( $message )->identifier( self::$filterIdentifier . '.' . $sanitizedFilterName )->build(),
		];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processActionCall( FuncCall $node ): array {
		if ( ! isset( $node->args[0] ) || ! $node->args[0] instanceof Arg || ! $node->args[0]->value instanceof String_ ) {
			return [];
		}

		$actionName = $node->args[0]->value->value;

		if ( ! isset( $this->actions[ $actionName ] ) ) {
			return [];
		}

		$since = $this->actions[ $actionName ]['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return $this->processHookParameters( 'Action', $actionName, $this->actions[ $actionName ], $node );
		}

		$message = sprintf(
			'Action %s is only available since %s version %s.',
			$actionName,
			'WordPress',
			$since
		);

		$sanitizedActionName = self::sanitizeIdentifier( $actionName );
		return [
			RuleErrorBuilder::message( $message )->identifier( self::$actionIdentifier . '.' . $sanitizedActionName )->build(),
		];
	}

	/**
	 * @param array{since: string, parameters?: array<int, array{name: string, since: string}>} $hookData
	 * @return list<IdentifierRuleError>
	 */
	private function processHookParameters( string $type, string $hookName, array $hookData, FuncCall $node ): array {
		if ( ! isset( $hookData['parameters'] ) ) {
			return [];
		}

		$acceptedArgs = 1;
		if ( isset( $node->args[3] ) && $node->args[3] instanceof Arg && $node->args[3]->value instanceof Node\Scalar\LNumber ) {
			$acceptedArgs = $node->args[3]->value->value;
		}

		$errors = [];
		foreach ( $hookData['parameters'] as $pos => $paramInfo ) {
			if ( $acceptedArgs < $pos ) {
				continue;
			}

			if ( version_compare( $paramInfo['since'], $this->minVersion, '<=' ) ) {
				continue;
			}

			$message = sprintf(
				'Parameter $%s of %s %s is only available since %s version %s.',
				$paramInfo['name'],
				strtolower( $type ),
				$hookName,
				'WordPress',
				$paramInfo['since']
			);

			$sanitizedHookName  = self::sanitizeIdentifier( $hookName );
			$sanitizedParamName = self::sanitizeIdentifier( $paramInfo['name'] );
			$identifier         = sprintf(
				'%s.%s.%s.%s',
				self::$parameterIdentifier,
				strtolower( $type ),
				$sanitizedHookName,
				$sanitizedParamName
			);
			$errors[]           = RuleErrorBuilder::message( $message )
				->identifier( $identifier )
				->build();
		}

		return $errors;
	}

	private function isInMethodExists( CallLike $node, Scope $scope ): bool {
		if ( ! $node instanceof MethodCall ) {
			return false;
		}

		$methodName = self::getMethodName( $node );

		$inMethodExists = $node->getAttribute( MethodExistsVisitor::ATTRIBUTE_NAME, [] );
		if ( ! is_array( $inMethodExists ) ) {
			return false;
		}

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
	 * @return list<IdentifierRuleError>
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
			if ( ! $this->reflectionProvider->hasClass( $className ) ) {
				continue;
			}

			$classReflection = $this->reflectionProvider->getClass( $className );
			$allClassNames   = array_merge( $allClassNames, $classReflection->getParentClassesNames() );
		}

		$methodName = self::getMethodName( $node );
		foreach ( $allClassNames as $className ) {
			try {
				$methodName = self::getMethodName( $node );
			} catch ( \RuntimeException $e ) {
				return [
					RuleErrorBuilder::message( $e->getMessage() )->identifier( self::$errorIdentifier )->build(),
				];
			}

			if ( ! is_string( $methodName ) ) {
				continue;
			}

			$name = sprintf(
				'%s::%s',
				$className,
				$methodName
			);

			if ( isset( $this->symbols[ $name ] ) ) {
				return $this->processMethodVersion( $name, $this->symbols[ $name ], $node, $scope );
			}
		}

		return [];
	}

	/**
	 * @param array{since: string, parameters?: array<string, array{since: string}>} $symbol
	 * @return list<IdentifierRuleError>
	 */
	private function processMethodVersion( string $name, array $symbol, CallLike $node, Scope $scope ): array {
		$since = $symbol['since'];

		if ( version_compare( $since, $this->minVersion, '<=' ) ) {
			return $this->processParameterVersions( $name, $symbol, $node, $scope );
		}

		$message = sprintf(
			'%s() is only available since %s version %s.',
			$name,
			'WordPress',
			$since
		);

		return [
			RuleErrorBuilder::message( $message )->identifier( self::$methodIdentifier )->build(),
		];
	}

	/**
	 * @param array{since: string, parameters?: array<string, array{since: string}>} $symbol
	 * @return list<IdentifierRuleError>
	 */
	private function processParameterVersions( string $name, array $symbol, CallLike $node, Scope $scope ): array {
		if ( ! isset( $symbol['parameters'] ) || count( $node->getArgs() ) === 0 ) {
			return [];
		}

		$paramReflections = $this->getParameterReflections( $node, $scope );
		$errors = [];

		foreach ( $node->getArgs() as $index => $arg ) {
			$paramName = null;

			if ( $arg->name instanceof Identifier ) {
				$paramName = $arg->name->toString();
			} elseif ( isset( $paramReflections[ $index ] ) ) {
				$paramName = $paramReflections[ $index ]->getName();
			}

			if ( ! is_string( $paramName ) || ! isset( $symbol['parameters'][ $paramName ] ) ) {
				continue;
			}

			$paramSince = $symbol['parameters'][ $paramName ]['since'];

			if ( version_compare( $paramSince, $this->minVersion, '<=' ) ) {
				continue;
			}

			$message = sprintf(
				'Parameter $%s of %s() is only available since %s version %s.',
				$paramName,
				$name,
				'WordPress',
				$paramSince
			);

			$sanitizedSymbolName = self::sanitizeIdentifier( $name );
			$sanitizedParamName  = self::sanitizeIdentifier( $paramName );
			$identifier          = sprintf(
				'%s.%s.%s',
				self::$parameterIdentifier,
				$sanitizedSymbolName,
				$sanitizedParamName
			);

			$errors[] = RuleErrorBuilder::message( $message )->identifier( $identifier )->build();
		}

		return $errors;
	}

	/**
	 * @return list<\PHPStan\Reflection\ParameterReflection>
	 */
	private function getParameterReflections( CallLike $node, Scope $scope ): array {
		try {
			if ( $node instanceof FuncCall ) {
				$funcName = self::getFunctionName( $node );
				if ( is_string( $funcName ) && $this->reflectionProvider->hasFunction( new Name( $funcName ), $scope ) ) {
					$reflection = $this->reflectionProvider->getFunction( new Name( $funcName ), $scope );
					return $reflection->getVariants()[0]->getParameters();
				}
			} elseif ( $node instanceof MethodCall ) {
				$methodName = self::getMethodName( $node );
				if ( is_string( $methodName ) ) {
					$type = $scope->getType( $node->var );
					if ( $type->hasMethod( $methodName )->yes() ) {
						$reflection = $type->getMethod( $methodName, $scope );
						return $reflection->getVariants()[0]->getParameters();
					}
				}
			} elseif ( $node instanceof StaticCall ) {
				$methodName = self::getMethodName( $node );
				if ( is_string( $methodName ) && $node->class instanceof Name ) {
					$className = $node->class->toString();
					if ( $this->reflectionProvider->hasClass( $className ) ) {
						$classReflection = $this->reflectionProvider->getClass( $className );
						if ( $classReflection->hasMethod( $methodName ) ) {
							$reflection = $classReflection->getMethod( $methodName, $scope );
							return $reflection->getVariants()[0]->getParameters();
						}
					}
				}
			}
		} catch ( \Throwable $e ) {
			return [];
		}

		return [];
	}

	private static function getFunctionName( FuncCall $node ): ?string {
		return $node->name instanceof Name ? $node->name->toString() : null;
	}

	/**
	 * @param MethodCall|StaticCall $node
	 */
	private static function getMethodName( CallLike $node ): ?string {
		return $node->name instanceof Identifier ? $node->name->toString() : null;
	}

	public function getMinVersion(): string {
		return $this->minVersion;
	}
}
