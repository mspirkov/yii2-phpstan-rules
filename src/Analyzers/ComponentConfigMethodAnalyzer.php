<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use MSpirkov\Yii2\PHPStan\Finders\MethodReturnExpressionFinder;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;

final class ComponentConfigMethodAnalyzer
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private MethodReturnExpressionFinder $returnExpressionFinder;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        MethodReturnExpressionFinder $returnExpressionFinder
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
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
            foreach ($this->validateReturnExpression($returnExpression, $scope, $validateArray) as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @param class-string $ownerClass
     */
    private function isClassScope(Scope $scope, string $ownerClass): bool
    {
        $classReflection = $scope->getClassReflection();

        return $classReflection instanceof ClassReflection
            && ($classReflection->is($ownerClass) || $classReflection->isSubclassOf($ownerClass));
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

        if (!$expr instanceof FuncCall || !$this->baseObjectConfigAnalyzer->isFunctionCallNamed($expr, 'array_merge')) {
            return [];
        }

        $errors = [];
        foreach ($expr->args as $arg) {
            if (!$arg instanceof Arg || !$arg->value instanceof Array_) {
                continue;
            }

            foreach ($validateArray($arg->value, $scope) as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }
}
