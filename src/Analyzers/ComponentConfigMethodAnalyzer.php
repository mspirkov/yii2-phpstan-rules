<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use MSpirkov\Yii2\PHPStan\Finders\MethodReturnExpressionFinder;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;

final class ComponentConfigMethodAnalyzer
{
    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    private MethodReturnExpressionFinder $returnExpressionFinder;

    public function __construct(
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver,
        MethodReturnExpressionFinder $returnExpressionFinder
    ) {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
        $this->returnExpressionFinder = $returnExpressionFinder;
    }

    /**
     * @param class-string $ownerClass
     * @param callable(Array_, Scope): list<IdentifierRuleError> $validateArray
     *
     * @return list<IdentifierRuleError>
     */
    public function analyze(
        ClassMethod $classMethod,
        Scope $scope,
        string $methodName,
        string $ownerClass,
        callable $validateArray
    ): array {
        if (strtolower($classMethod->name->name) !== strtolower($methodName)) {
            return [];
        }

        if (!$this->isClassScope($scope, $ownerClass)) {
            return [];
        }

        $errors = [];
        foreach ($this->returnExpressionFinder->find($classMethod->stmts ?? []) as $returnExpression) {
            $errors = array_merge($errors, $this->validateReturnExpression($returnExpression, $scope, $validateArray));
        }

        return $errors;
    }

    /**
     * @param class-string $ownerClass
     */
    private function isClassScope(Scope $scope, string $ownerClass): bool
    {
        return $this->expressionTypeAnalyzer->isClassReflectionOf($scope->getClassReflection(), $ownerClass);
    }

    /**
     * @param callable(Array_, Scope): list<IdentifierRuleError> $validateArray
     *
     * @return list<IdentifierRuleError>
     */
    private function validateReturnExpression(Expr $expr, Scope $scope, callable $validateArray): array
    {
        if ($expr instanceof Array_) {
            return $validateArray($expr, $scope);
        }

        if (!$expr instanceof FuncCall || !$this->expressionValueResolver->isFunctionCallNamed($expr, 'array_merge')) {
            return [];
        }

        $errors = [];
        foreach ($expr->args as $arg) {
            if (!$arg instanceof Arg || !$arg->value instanceof Array_) {
                continue;
            }

            $errors = array_merge($errors, $validateArray($arg->value, $scope));
        }

        return $errors;
    }
}
