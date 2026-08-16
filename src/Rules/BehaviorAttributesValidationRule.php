<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\Db\ActiveRecord\DateTimeBehavior;
use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectPropertyAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentConfigMethodAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ModelAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Model;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\AttributeTypecastBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * @implements Rule<ClassMethod>
 */
final class BehaviorAttributesValidationRule implements Rule
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer;

    private ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    private ModelAnalyzer $modelAnalyzer;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer,
        ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver,
        ModelAnalyzer $modelAnalyzer
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->baseObjectPropertyAnalyzer = $baseObjectPropertyAnalyzer;
        $this->componentConfigMethodAnalyzer = $componentConfigMethodAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
        $this->modelAnalyzer = $modelAnalyzer;
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
            Model::class,
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
            if ($item->unpack || !$item->value instanceof Array_) {
                continue;
            }

            $errors = array_merge($errors, $this->validateBehaviorArray($item->value, $scope));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateBehaviorArray(Array_ $behaviorConfig, Scope $scope): array
    {
        $items = $this->baseObjectConfigAnalyzer->collectStaticItems($behaviorConfig);
        $classItem = $items['__class'] ?? $items['class'] ?? null;
        if (!$classItem instanceof ArrayItem) {
            return [];
        }

        $className = $this->expressionValueResolver->getSingleStringValue($classItem->value, $scope);
        if ($className === null || !$this->expressionTypeAnalyzer->hasClass($className)) {
            return [];
        }

        $options = $this->baseObjectConfigAnalyzer->collectOptions($items, 0)['items'];
        $errors = [];

        if ($this->expressionTypeAnalyzer->isClassNameOf($className, AttributeBehavior::class)) {
            $errors = array_merge($errors, $this->validateAttributesMapOption($options, $scope));
        }

        if (
            $this->expressionTypeAnalyzer->isClassNameOf($className, TimestampBehavior::class)
            || $this->expressionTypeAnalyzer->isClassNameOf($className, DateTimeBehavior::class)
        ) {
            $errors = array_merge(
                $errors,
                $this->validateAttributeNameOption($options, 'createdAtAttribute', $scope),
                $this->validateAttributeNameOption($options, 'updatedAtAttribute', $scope)
            );
        }

        if ($this->expressionTypeAnalyzer->isClassNameOf($className, BlameableBehavior::class)) {
            $errors = array_merge(
                $errors,
                $this->validateAttributeNameOption($options, 'createdByAttribute', $scope),
                $this->validateAttributeNameOption($options, 'updatedByAttribute', $scope)
            );
        }

        if ($this->expressionTypeAnalyzer->isClassNameOf($className, SluggableBehavior::class)) {
            $errors = array_merge(
                $errors,
                $this->validateAttributeNameOrListOption($options, 'attribute', $scope),
                $this->validateAttributeNameOption($options, 'slugAttribute', $scope)
            );
        }

        if ($this->expressionTypeAnalyzer->isClassNameOf($className, AttributeTypecastBehavior::class)) {
            return array_merge($errors, $this->validateAttributeTypesOption($options, $scope));
        }

        return $errors;
    }

    /**
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateAttributesMapOption(array $options, Scope $scope): array
    {
        $item = $options['attributes'] ?? null;
        if ($item === null || !$item->value instanceof Array_) {
            return [];
        }

        $errors = [];
        foreach ($item->value->items as $entry) {
            if ($entry->unpack) {
                continue;
            }

            $errors = array_merge($errors, $this->validateAttributeNameOrListExpr($entry->value, $scope));
        }

        return $errors;
    }

    /**
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeNameOption(array $options, string $optionName, Scope $scope): array
    {
        $item = $options[$optionName] ?? null;
        if ($item === null || !$item->value instanceof String_) {
            return [];
        }

        return $this->validateAttributeExists($item->value->value, $item->value, $scope);
    }

    /**
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeNameOrListOption(array $options, string $optionName, Scope $scope): array
    {
        $item = $options[$optionName] ?? null;
        if ($item === null) {
            return [];
        }

        return $this->validateAttributeNameOrListExpr($item->value, $scope);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeNameOrListExpr(Expr $expr, Scope $scope): array
    {
        if ($expr instanceof String_) {
            return $this->validateAttributeExists($expr->value, $expr, $scope);
        }

        if (!$expr instanceof Array_) {
            return [];
        }

        $errors = [];
        foreach ($expr->items as $item) {
            if ($item->unpack) {
                continue;
            }

            $errors = array_merge($errors, $this->validateAttributeNameOrListExpr($item->value, $scope));
        }

        return $errors;
    }

    /**
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeTypesOption(array $options, Scope $scope): array
    {
        $item = $options['attributeTypes'] ?? null;
        if ($item === null || !$item->value instanceof Array_) {
            return [];
        }

        $errors = [];
        foreach ($this->baseObjectConfigAnalyzer->collectStaticItems($item->value) as $attributeName => $entry) {
            if (!is_string($attributeName)) {
                continue;
            }

            $errors = array_merge($errors, $this->validateAttributeExists($attributeName, $entry, $scope));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeExists(string $attributeName, Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if (
            $this->modelAnalyzer->shouldSkipAttributeExistenceCheck($classReflection)
            || !$this->baseObjectPropertyAnalyzer->isUnknownAttribute($classReflection, $attributeName)
        ) {
            return [];
        }

        return [
            $this->buildError(
                sprintf('Unknown attribute "%s" for model %s.', $attributeName, $classReflection->getName()),
                $node
            ),
        ];
    }

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return ErrorBuilder::build($message, Identifiers::BEHAVIOR_ATTRIBUTES_VALIDATION, $node->getStartLine());
    }
}
