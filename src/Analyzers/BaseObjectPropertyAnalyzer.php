<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;

final class BaseObjectPropertyAnalyzer
{
    public function findInstanceProperty(
        ClassReflection $classReflection,
        string $propertyName,
        Scope $scope
    ): ?ExtendedPropertyReflection {
        if ($classReflection->hasInstanceProperty($propertyName)) {
            return $classReflection->getInstanceProperty($propertyName, $scope);
        }

        return null;
    }

    public function hasWritableProperty(
        ClassReflection $classReflection,
        string $propertyName,
        Scope $scope
    ): bool {
        $instanceProperty = $this->findInstanceProperty($classReflection, $propertyName, $scope);
        if ($instanceProperty !== null) {
            if ($instanceProperty->isWritable()) {
                return true;
            }
        }

        return $this->hasPropertySetter($classReflection, $propertyName);
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->hasInstanceProperty($propertyName)) {
            return true;
        }

        if ($this->hasPropertyGetter($classReflection, $propertyName)) {
            return true;
        }

        return $this->hasPropertySetter($classReflection, $propertyName);
    }

    private function hasPropertySetter(ClassReflection $classReflection, string $propertyName): bool
    {
        $accessorName = ucfirst($propertyName);

        return $classReflection->hasMethod('set' . $accessorName);
    }

    private function hasPropertyGetter(ClassReflection $classReflection, string $propertyName): bool
    {
        $accessorName = ucfirst($propertyName);

        return $classReflection->hasMethod('get' . $accessorName);
    }
}
