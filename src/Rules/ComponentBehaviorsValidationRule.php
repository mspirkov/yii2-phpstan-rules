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
                foreach ($this->validateBehaviorArray($item->value, $scope) as $error) {
                    $errors[] = $error;
                }

                continue;
            }

            if ($this->expressionTypeAnalyzer->isObjectOf($item->value, $scope, Behavior::class)) {
                continue;
            }

            foreach ($this->validateBehaviorClassExpression($item->value, $scope) as $error) {
                $errors[] = $error;
            }
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
            $errors[] = $this->buildError('Component behavior configuration must specify "class" or "__class".', $behaviorConfig);

            return $errors;
        }

        $classExpr = $classItem->value;
        if ($this->expressionValueResolver->isNullExpression($classExpr)) {
            $errors[] = $this->buildError('Component behavior class cannot be null.', $classExpr);

            return $errors;
        }

        if ($this->expressionTypeAnalyzer->isDefinitelyNotString($classExpr, $scope)) {
            $errors[] = $this->buildError('Component behavior class must be a string.', $classExpr);

            return $errors;
        }

        $behaviorClass = $this->resolveBehaviorClass($classExpr, $scope, $errors);
        if ($behaviorClass === null) {
            return $errors;
        }

        $errors = array_merge($errors, $this->baseObjectConfigAnalyzer->validateObjectOptionNames(
            $behaviorClass,
            $options['items'],
            'behavior',
            Identifiers::COMPONENT_BEHAVIORS_VALIDATION
        ));

        return array_merge($errors, $this->baseObjectConfigAnalyzer->validateObjectOptionValueTypes(
            $behaviorClass,
            $options['items'],
            $scope,
            'Behavior',
            [],
            Identifiers::COMPONENT_BEHAVIORS_VALIDATION
        ));
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateBehaviorClassExpression(Expr $expr, Scope $scope): array
    {
        if ($this->expressionTypeAnalyzer->isDefinitelyNotString($expr, $scope)) {
            return [
                $this->buildError(
                    'Component behavior must be a class string, configuration array, or yii\base\Behavior instance.',
                    $expr
                ),
            ];
        }

        $errors = [];
        $this->resolveBehaviorClass($expr, $scope, $errors);

        return $errors;
    }

    /**
     * @param list<IdentifierRuleError> $errors
     *
     * @return class-string<Behavior>|null
     */
    private function resolveBehaviorClass(Expr $expr, Scope $scope, array &$errors): ?string
    {
        $className = $this->expressionValueResolver->getSingleStringValue($expr, $scope);
        if ($className === null) {
            return null;
        }

        if (!$this->expressionTypeAnalyzer->hasClass($className)) {
            $errors[] = $this->buildError(sprintf('Unknown behavior class "%s".', $className), $expr);

            return null;
        }

        if (!$this->expressionTypeAnalyzer->isClassNameOf($className, Behavior::class)) {
            $errors[] = $this->buildError(
                sprintf('Component behavior class "%s" must be yii\base\Behavior or its subclass.', $className),
                $expr
            );

            return null;
        }

        return $className;
    }

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return ErrorBuilder::build($message, Identifiers::COMPONENT_BEHAVIORS_VALIDATION, $node->getStartLine());
    }
}
