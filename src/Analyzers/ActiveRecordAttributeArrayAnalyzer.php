<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use MSpirkov\Yii2\PHPStan\Rules\ErrorBuilder;
use MSpirkov\Yii2\PHPStan\Rules\Identifiers;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use yii\db\ExpressionInterface;

final class ActiveRecordAttributeArrayAnalyzer
{
    private BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    public function __construct(
        BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->baseObjectPropertyAnalyzer = $baseObjectPropertyAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
    }

    /**
     * @param value-of<Identifiers::LIST> $identifier
     *
     * @return list<IdentifierRuleError>
     */
    public function validateAttributeValues(
        Array_ $array,
        ClassReflection $classReflection,
        Scope $scope,
        string $argDescription,
        bool $allowArrayValue,
        string $identifier
    ): array {
        $errors = [];

        foreach ($array->items as $item) {
            if ($item->unpack || $item->key === null) {
                continue;
            }

            $attributeName = $this->expressionValueResolver->getSingleStringValue($item->key, $scope);
            if ($attributeName === null) {
                continue;
            }

            if ($this->baseObjectPropertyAnalyzer->isUnknownAttribute($classReflection, $attributeName)) {
                $errors[] = ErrorBuilder::build(
                    sprintf(
                        'Unknown attribute "%s" for ActiveRecord %s in %s.',
                        $attributeName,
                        $classReflection->getName(),
                        $argDescription
                    ),
                    $identifier,
                    $item->key->getStartLine()
                );

                continue;
            }

            $errors = array_merge(
                $errors,
                $this->validateAttributeValueType(
                    $classReflection,
                    $attributeName,
                    $item,
                    $scope,
                    $argDescription,
                    $allowArrayValue,
                    $identifier
                )
            );
        }

        return $errors;
    }

    /**
     * @param value-of<Identifiers::LIST> $identifier
     *
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeValueType(
        ClassReflection $classReflection,
        string $attributeName,
        ArrayItem $item,
        Scope $scope,
        string $argDescription,
        bool $allowArrayValue,
        string $identifier
    ): array {
        $instanceProperty = $this->baseObjectPropertyAnalyzer->findInstanceProperty(
            $classReflection,
            $attributeName,
            $scope
        );

        if ($instanceProperty === null || !$instanceProperty->isWritable()) {
            return [];
        }

        $expectedType = $instanceProperty->getWritableType();
        $actualType = $scope->getType($item->value);

        if ($allowArrayValue && $actualType->isArray()->yes()) {
            $actualType = $actualType->getIterableValueType();
        }

        if ($expectedType instanceof MixedType || $actualType instanceof MixedType) {
            return [];
        }

        $acceptedType = TypeCombinator::union($expectedType, new ObjectType(ExpressionInterface::class));

        if (!$acceptedType->accepts($actualType, true)->no()) {
            return [];
        }

        return [
            ErrorBuilder::build(
                sprintf(
                    'Value for attribute "%s" on ActiveRecord %s in %s must be %s, %s given.',
                    $attributeName,
                    $classReflection->getName(),
                    $argDescription,
                    $expectedType->describe(VerbosityLevel::typeOnly()),
                    $actualType->describe(VerbosityLevel::typeOnly())
                ),
                $identifier,
                $item->value->getStartLine()
            ),
        ];
    }
}
