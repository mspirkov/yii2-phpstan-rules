<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ActionComplexityAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Controller;

/**
 * @implements Rule<ClassMethod>
 */
final class NoComplexControllerActionsRule implements Rule
{
    private ActionComplexityAnalyzer $actionComplexityAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(
        ActionComplexityAnalyzer $actionComplexityAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer
    ) {
        $this->actionComplexityAnalyzer = $actionComplexityAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->expressionTypeAnalyzer->isClassReflectionOf($scope->getClassReflection(), Controller::class)) {
            return [];
        }

        if (!$this->isActionMethod($node)) {
            return [];
        }

        $errors = [];

        foreach ($this->actionComplexityAnalyzer->getExceededLimits($node) as $counterName => $violation) {
            $errors[] = ErrorBuilder::build(
                sprintf(
                    'Controller action contains too much business logic: %s is %d, allowed %d. '
                        . 'Move business logic to the service layer.',
                    $counterName,
                    $violation['actual'],
                    $violation['allowed']
                ),
                Identifiers::NO_COMPLEX_CONTROLLER_ACTIONS,
                $violation['line']
            );
        }

        return $errors;
    }

    private function isActionMethod(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->name;

        return $methodName !== 'actions' && strpos($methodName, 'action') === 0;
    }
}
