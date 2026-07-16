<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

final class ActiveRecordRelationAnalyzer
{
    /** @var list<class-string> */
    private const GENERIC_DEFAULT_CLASSES = [ActiveRecord::class, BaseActiveRecord::class];

    private BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(
        BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer
    ) {
        $this->baseObjectPropertyAnalyzer = $baseObjectPropertyAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
    }

    public function resolveQueryModelClass(Type $queryType): ?ClassReflection
    {
        $classReflection = $this->expressionTypeAnalyzer->getSingleClassReflectionOfType(
            $queryType->getTemplateType(ActiveQuery::class, 'T'),
            BaseActiveRecord::class
        );

        if ($classReflection === null || in_array($classReflection->getName(), self::GENERIC_DEFAULT_CLASSES, true)) {
            return null;
        }

        return $classReflection;
    }

    public function hasRelation(
        ClassReflection $classReflection,
        string $relationName,
        Scope $scope
    ): bool {
        $methodName = 'get' . ucfirst($relationName);
        if (!$classReflection->hasMethod($methodName)) {
            return false;
        }

        return !(new ObjectType(ActiveQueryInterface::class))
            ->isSuperTypeOf($this->getMethodReturnType($classReflection, $methodName, $scope))
            ->no();
    }

    public function resolveRelatedClass(
        ClassReflection $classReflection,
        string $relationName,
        Scope $scope
    ): ?ClassReflection {
        if (!$this->hasRelation($classReflection, $relationName, $scope)) {
            return null;
        }

        $returnType = $this->getMethodReturnType($classReflection, 'get' . ucfirst($relationName), $scope);
        $relatedClass = $this->resolveQueryModelClass($returnType);
        if ($relatedClass !== null) {
            return $relatedClass;
        }

        $property = $this->baseObjectPropertyAnalyzer->findInstanceProperty($classReflection, $relationName, $scope);
        if ($property === null) {
            return null;
        }

        $propertyType = $property->getReadableType();
        if ($propertyType->isArray()->yes()) {
            $propertyType = $propertyType->getIterableValueType();
        }

        return $this->expressionTypeAnalyzer->getSingleClassReflectionOfType($propertyType, BaseActiveRecord::class);
    }

    private function getMethodReturnType(ClassReflection $classReflection, string $methodName, Scope $scope): Type
    {
        return ParametersAcceptorSelector::selectFromTypes(
            [],
            $classReflection->getMethod($methodName, $scope)->getVariants(),
            false
        )->getReturnType();
    }
}
