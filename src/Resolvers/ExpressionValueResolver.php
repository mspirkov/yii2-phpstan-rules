<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Resolvers;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\BooleanType;

final class ExpressionValueResolver
{
    public function getConstantBoolean(Expr $expr, Scope $scope): ?bool
    {
        $type = $scope->getType($expr);

        if ($type->isTrue()->yes()) {
            return true;
        }

        if ($type->isFalse()->yes()) {
            return false;
        }

        return null;
    }

    public function getSingleStringValue(Expr $expr, Scope $scope): ?string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        $constantStrings = $scope->getType($expr)->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }

    public function isFunctionCallNamed(FuncCall $funcCall, string $name): bool
    {
        return $funcCall->name instanceof Name && strtolower($funcCall->name->toString()) === $name;
    }

    public function isNullExpression(Expr $expr): bool
    {
        return $expr instanceof ConstFetch && strtolower($expr->name->toString()) === 'null';
    }
}
