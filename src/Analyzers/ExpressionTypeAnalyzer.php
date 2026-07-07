<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;

final class ExpressionTypeAnalyzer
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function hasClass(string $className): bool
    {
        return $this->reflectionProvider->hasClass($className);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $parentClass
     *
     * @phpstan-assert-if-true class-string<T> $className
     */
    public function isClassNameOf(string $className, string $parentClass): bool
    {
        if (!$this->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return $classReflection->is($parentClass) || $classReflection->isSubclassOf($parentClass);
    }

    /**
     * @param class-string $className
     */
    public function isDefinitelyNotArrayOrObjectOf(Expr $expr, Scope $scope, string $className): bool
    {
        $type = $scope->getType($expr);
        if (!$type->isArray()->no()) {
            return false;
        }

        return (new ObjectType($className))->isSuperTypeOf($type)->no();
    }

    public function isDefinitelyNotString(Expr $expr, Scope $scope): bool
    {
        return $scope->getType($expr)->isString()->no();
    }

    /**
     * @param class-string $className
     */
    public function isObjectOf(Expr $expr, Scope $scope, string $className): bool
    {
        return (new ObjectType($className))->isSuperTypeOf($scope->getType($expr))->yes();
    }
}
