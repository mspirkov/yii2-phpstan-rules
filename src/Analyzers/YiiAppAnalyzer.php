<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use yii\base\Application;

final class YiiAppAnalyzer
{
    public function isPropertyFetch(Expr $expr, Scope $scope): bool
    {
        $type = TypeCombinator::removeNull($scope->getType($expr));

        return (new ObjectType(Application::class))->isSuperTypeOf($type)->yes();
    }
}
