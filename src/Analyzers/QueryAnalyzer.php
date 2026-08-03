<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use yii\db\ActiveQueryInterface;
use yii\db\QueryInterface;

final class QueryAnalyzer
{
    /** @var list<class-string> */
    private const QUERY_CLASSES = [
        ActiveQueryInterface::class,
        QueryInterface::class,
    ];

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(ExpressionTypeAnalyzer $expressionTypeAnalyzer)
    {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
    }

    public function isQueryExpression(Expr $expr, Scope $scope): bool
    {
        return $this->expressionTypeAnalyzer->isTypeAnyOf($scope->getType($expr), self::QUERY_CLASSES);
    }
}
