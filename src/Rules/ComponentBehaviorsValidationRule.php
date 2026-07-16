<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentConfigMethodAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Behavior;
use yii\base\Component;

/**
 * @implements Rule<ClassMethod>
 */
final class ComponentBehaviorsValidationRule implements Rule
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->componentConfigMethodAnalyzer = $componentConfigMethodAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
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
        return $this->componentConfigMethodAnalyzer->analyze(
            $node,
            $scope,
            'behaviors',
            Component::class,
            fn(Array_ $behaviors, Scope $scope): array => $this->validateBehaviorsList($behaviors, $scope)
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateBehaviorsList(Array_ $behaviors, Scope $scope): array
    {
        $errors = [];

        foreach ($behaviors->items as $item) {
            if ($item->unpack) {
                continue;
            }

            if ($item->value instanceof Array_) {
                $errors = array_merge($errors, $this->validateBehaviorArray($item->value, $scope));

                continue;
            }

            if ($this->expressionTypeAnalyzer->isObjectOf($item->value, $scope, Behavior::class)) {
                continue;
            }

            $errors = array_merge($errors, $this->checkBehaviorClass($item->value, $scope)['errors']);
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateBehaviorArray(Array_ $behaviorConfig, Scope $scope): array
    {
        $errors = [];
        $items = $this->baseObjectConfigAnalyzer->collectStaticItems($behaviorConfig);
        $options = $this->baseObjectConfigAnalyzer->collectOptions($items, 0);

        foreach ($options['invalidKeys'] as $invalidKey) {
            $errors[] = $this->buildError('Component behavior configuration option keys must be strings.', $invalidKey);
        }

        $classItem = $items['__class'] ?? $items['class'] ?? null;
        if (!$classItem instanceof ArrayItem) {
            return $errors;
        }

        $result = $this->checkBehaviorClass($classItem->value, $scope);
        $errors = array_merge($errors, $result['errors']);
        if ($result['class'] === null) {
            return $errors;
        }

        $errors = array_merge($errors, $this->baseObjectConfigAnalyzer->validateObjectOptionNames(
            $result['class'],
            $options['items'],
            $scope,
            'behavior',
            Identifiers::COMPONENT_BEHAVIORS_VALIDATION
        ));

        return array_merge($errors, $this->baseObjectConfigAnalyzer->validateObjectOptionValueTypes(
            $result['class'],
            $options['items'],
            $scope,
            'Behavior',
            [],
            Identifiers::COMPONENT_BEHAVIORS_VALIDATION
        ));
    }

    /**
     * @return array{class: class-string<Behavior>|null, errors: list<IdentifierRuleError>}
     */
    private function checkBehaviorClass(Expr $expr, Scope $scope): array
    {
        $className = $this->expressionValueResolver->getSingleStringValue($expr, $scope);
        if ($className === null || !$this->expressionTypeAnalyzer->hasClass($className)) {
            return ['class' => null, 'errors' => []];
        }

        if (!$this->expressionTypeAnalyzer->isClassNameOf($className, Behavior::class)) {
            return [
                'class' => null,
                'errors' => [
                    $this->buildError(
                        sprintf('Component behavior class "%s" must be yii\base\Behavior or its subclass.', $className),
                        $expr
                    ),
                ],
            ];
        }

        return ['class' => $className, 'errors' => []];
    }

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return ErrorBuilder::build($message, Identifiers::COMPONENT_BEHAVIORS_VALIDATION, $node->getStartLine());
    }
}
