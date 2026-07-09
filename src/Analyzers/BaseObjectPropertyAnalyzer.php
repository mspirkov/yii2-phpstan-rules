<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;

final class BaseObjectPropertyAnalyzer
{
    public function hasWritableProperty(ClassReflection $classReflection, string $propertyName, Scope $scope): bool
    {
        if ($classReflection->hasInstanceProperty($propertyName)) {
            $propertyReflection = $classReflection->getInstanceProperty($propertyName, $scope);
            if ($propertyReflection->isWritable()) {
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
