<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\QueryAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<BinaryOp>
 */
final class NoRedundantExistenceCheckRule implements Rule
{
    private QueryAnalyzer $queryAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    public function __construct(
        QueryAnalyzer $queryAnalyzer,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->queryAnalyzer = $queryAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
    }

    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Identical || $node instanceof NotIdentical) {
            return $this->processEqualityComparison($node, $scope);
        }

        if (
            $node instanceof Greater
            || $node instanceof Smaller
            || $node instanceof GreaterOrEqual
            || $node instanceof SmallerOrEqual
        ) {
            return $this->processRelationalComparison($node, $scope);
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processEqualityComparison(BinaryOp $node, Scope $scope): array
    {
        $isNotIdentical = $node instanceof NotIdentical;

        $oneOther = $this->matchQueryMethodCall($node->left, $node->right, 'one', $scope);
        if ($oneOther !== null && $this->expressionValueResolver->isNullExpression($oneOther)) {
            return [$this->buildError($node, 'one', !$isNotIdentical)];
        }

        $countOther = $this->matchQueryMethodCall($node->left, $node->right, 'count', $scope);
        if ($countOther !== null && $this->expressionValueResolver->getSingleIntValue($countOther, $scope) === 0) {
            return [$this->buildError($node, 'count', !$isNotIdentical)];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processRelationalComparison(BinaryOp $node, Scope $scope): array
    {
        if ($node instanceof Greater || $node instanceof GreaterOrEqual) {
            $greaterSide = $node->left;
            $lesserSide = $node->right;
        } else {
            $greaterSide = $node->right;
            $lesserSide = $node->left;
        }

        $orEqual = $node instanceof GreaterOrEqual || $node instanceof SmallerOrEqual;
        $existsThreshold = $orEqual ? 1 : 0;
        $notExistsThreshold = $orEqual ? 0 : 1;

        if (
            $this->isQueryMethodCall($greaterSide, 'count', $scope)
            && $this->expressionValueResolver->getSingleIntValue($lesserSide, $scope) === $existsThreshold
        ) {
            return [$this->buildError($node, 'count', false)];
        }

        if (
            $this->expressionValueResolver->getSingleIntValue($greaterSide, $scope) === $notExistsThreshold
            && $this->isQueryMethodCall($lesserSide, 'count', $scope)
        ) {
            return [$this->buildError($node, 'count', true)];
        }

        return [];
    }

    private function matchQueryMethodCall(Expr $left, Expr $right, string $methodName, Scope $scope): ?Expr
    {
        if ($this->isQueryMethodCall($left, $methodName, $scope)) {
            return $right;
        }

        if ($this->isQueryMethodCall($right, $methodName, $scope)) {
            return $left;
        }

        return null;
    }

    private function isQueryMethodCall(Expr $expr, string $methodName, Scope $scope): bool
    {
        if (!$expr instanceof MethodCall || !$expr->name instanceof Identifier) {
            return false;
        }

        if (strtolower($expr->name->name) !== $methodName) {
            return false;
        }

        return $this->queryAnalyzer->isQueryExpression($expr->var, $scope);
    }

    private function buildError(BinaryOp $node, string $sourceMethod, bool $negated): IdentifierRuleError
    {
        return ErrorBuilder::build(
            sprintf(
                'Comparing the result of Query::%s() only checks whether a matching row exists. Use %sQuery::exists() instead.',
                $sourceMethod,
                $negated ? '!' : ''
            ),
            Identifiers::NO_REDUNDANT_EXISTENCE_CHECK,
            $node->getStartLine()
        );
    }
}
