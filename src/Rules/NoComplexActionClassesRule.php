<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Services\ActionComplexityAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
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

        return $this->buildErrors($node, 'Action class', Identifiers::NO_COMPLEX_ACTION_CLASSES);
    }

    private function isAction(?ClassReflection $classReflection): bool
    {
        return $classReflection instanceof ClassReflection
            && ($classReflection->is(Action::class) || $classReflection->isSubclassOf(Action::class));
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function buildErrors(ClassMethod $classMethod, string $context, string $identifier): array
    {
        $errors = [];

        foreach ($this->actionComplexityAnalyzer->getExceededLimits($classMethod) as $counterName => $violation) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                '%s contains too much business logic: %s is %d, allowed %d. Move business logic to the service layer.',
                $context,
                $counterName,
                $violation['actual'],
                $violation['allowed']
            ))
                ->line($violation['line'])
                ->identifier($identifier)
                ->build();
        }

        return $errors;
    }
}
