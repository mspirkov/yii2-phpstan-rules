<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use yii\base\DynamicModel;
use yii\base\Model;

final class ModelAnalyzer
{
    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(ExpressionTypeAnalyzer $expressionTypeAnalyzer)
    {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
    }

    public function resolveModelClassForAttributeChecks(Expr $expr, Scope $scope): ?ClassReflection
    {
        $classReflection = $this->expressionTypeAnalyzer->getSingleClassReflectionOf(
            $expr,
            $scope,
            Model::class
        );

        return $this->shouldSkipAttributeExistenceCheck($classReflection) ? null : $classReflection;
    }

    /**
     * @phpstan-assert-if-false ClassReflection $classReflection
     */
    public function shouldSkipAttributeExistenceCheck(?ClassReflection $classReflection): bool
    {
        if ($classReflection === null) {
            return true;
        }

        return $this->expressionTypeAnalyzer->isClassReflectionOf($classReflection, DynamicModel::class);
    }
}
