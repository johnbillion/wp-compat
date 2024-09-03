<?php declare(strict_types=1);

namespace WPCompat\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;

class SinceVersionRule implements Rule {
	private array $symbols;

	public function __construct() {
		$symbolsFilePath = dirname( __DIR__, 2 ) . '/symbols.json';
		$this->symbols = json_decode(file_get_contents($symbolsFilePath), true)['symbols'];
	}

	public function getNodeType(): string {
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array {
		return [];
	}
}
