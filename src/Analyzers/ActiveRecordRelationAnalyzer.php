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

    /**
     * Resolves the concrete ActiveRecord class an `ActiveQuery<T>`-shaped type is querying
     * (e.g. `Customer::find()` statically returns `ActiveQuery<Customer>`), returning null if
     * the type isn't a generic `ActiveQuery`/`ActiveQueryInterface` bound to a single AR class.
     *
     * `ActiveQuery`'s `@template T` declares a default (`ActiveRecord|array<array-key, mixed>`)
     * for when no concrete type argument is given (e.g. a plain `ActiveQuery $query` parameter
     * with no user-specified subclass), so a resolved class of exactly `ActiveRecord` or
     * `BaseActiveRecord` means "unbound", not "a real model" — treat it the same as null.
     */
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

    /**
     * Whether $classReflection declares a relation named $relationName: a `getXxx()` method
     * (Yii's relation naming convention) whose return type isn't definitely incompatible with
     * `ActiveQueryInterface`, mirroring `BaseActiveRecord::getRelation()`'s own check. Many
     * relation getters have no return type declared at all (a common, long-standing Yii2
     * pattern), which resolves to `mixed` rather than a confirmed `ActiveQueryInterface` — so
     * this only rules a method out when its return type is *known* to be something else
     * entirely (e.g. a plain property getter returning `string`).
     */
    public function hasRelation(ClassReflection $classReflection, string $relationName, Scope $scope): bool
    {
        $methodName = 'get' . ucfirst($relationName);
        if (!$classReflection->hasMethod($methodName)) {
            return false;
        }

        return !(new ObjectType(ActiveQueryInterface::class))
            ->isSuperTypeOf($this->getMethodReturnType($classReflection, $methodName, $scope))
            ->no();
    }

    /**
     * Resolves the related ActiveRecord class for a given relation, if statically known: either
     * via the relation getter's `ActiveQuery<X>` return type, or via a `@property-read X` /
     * `@property-read X[]` PHPDoc property of the same name. Returns null when the relation
     * doesn't exist, or its related class can't be determined with confidence.
     */
    public function resolveRelatedClass(ClassReflection $classReflection, string $relationName, Scope $scope): ?ClassReflection
    {
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
