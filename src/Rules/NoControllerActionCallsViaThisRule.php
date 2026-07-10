<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Controller;

/**
 * @implements Rule<MethodCall>
 */
final class NoControllerActionCallsViaThisRule implements Rule
{
    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(ExpressionTypeAnalyzer $expressionTypeAnalyzer)
    {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->expressionTypeAnalyzer->isClassReflectionOf($scope->getClassReflection(), Controller::class)) {
            return [];
        }

        if (!$this->isThisCall($node)) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->name;
        if (!$this->isActionMethodName($methodName)) {
            return [];
        }

        return [
            ErrorBuilder::build(
                sprintf(
                    'Calling controller action %s() via $this is forbidden. Move shared logic to a service '
                        . 'or a private method, or perform a redirect.',
                    $methodName
                ),
                Identifiers::NO_CONTROLLER_ACTION_CALLS_VIA_THIS,
            ),
        ];
    }

    private function isThisCall(MethodCall $methodCall): bool
    {
        return $methodCall->var instanceof Variable && $methodCall->var->name === 'this';
    }

    private function isActionMethodName(string $methodName): bool
    {
        return $methodName !== 'actions' && strpos($methodName, 'action') === 0;
    }
}
