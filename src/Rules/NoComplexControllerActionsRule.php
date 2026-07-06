<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ActionComplexityAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Controller;

/**
 * @implements Rule<ClassMethod>
 */
final class NoComplexControllerActionsRule implements Rule
{
    private ActionComplexityAnalyzer $actionComplexityAnalyzer;

    public function __construct(ActionComplexityAnalyzer $actionComplexityAnalyzer)
    {
        $this->actionComplexityAnalyzer = $actionComplexityAnalyzer;
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isController($scope->getClassReflection())) {
            return [];
        }

        if (!$this->isActionMethod($node)) {
            return [];
        }

        return $this->buildErrors($node);
    }

    private function isController(?ClassReflection $classReflection): bool
    {
        return $classReflection instanceof ClassReflection
            && ($classReflection->is(Controller::class) || $classReflection->isSubclassOf(Controller::class));
    }

    private function isActionMethod(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->name;

        return $methodName !== 'actions' && strpos($methodName, 'action') === 0;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function buildErrors(ClassMethod $classMethod): array
    {
        $errors = [];

        foreach ($this->actionComplexityAnalyzer->getExceededLimits($classMethod) as $counterName => $violation) {
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
}
