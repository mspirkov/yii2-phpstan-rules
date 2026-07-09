<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PHPStan\Reflection\ClassReflection;

final class BaseObjectPropertyAnalyzer
{
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->hasInstanceProperty($propertyName)) {
            return true;
        }

        $accessorName = ucfirst($propertyName);

        return $classReflection->hasMethod('get' . $accessorName) || $classReflection->hasMethod('set' . $accessorName);
    }
}
