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
use yii\base\Action;

/**
 * @implements Rule<ClassMethod>
 */
final class NoComplexActionClassesRule implements Rule
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
        if (!$this->isAction($scope->getClassReflection())) {
            return [];
        }

        if ($node->name->name !== 'run') {
            return [];
        }

        $errors = [];

        foreach ($this->actionComplexityAnalyzer->getExceededLimits($node) as $counterName => $violation) {
            $errors[] = ErrorBuilder::build(
                sprintf(
                    'Action class contains too much business logic: %s is %d, allowed %d. '
                        . 'Move business logic to the service layer.',
                    $counterName,
                    $violation['actual'],
                    $violation['allowed']
                ),
                Identifiers::NO_COMPLEX_ACTION_CLASSES,
                $violation['line']
            );
        }

        return $errors;
    }

    private function isAction(?ClassReflection $classReflection): bool
    {
        return $classReflection instanceof ClassReflection
            && ($classReflection->is(Action::class) || $classReflection->isSubclassOf(Action::class));
    }
}
