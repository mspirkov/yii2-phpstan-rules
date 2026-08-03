<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\QueryAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<MethodCall>
 */
final class QueryConditionValidationRule implements Rule
{
    /** @var list<string> */
    private const METHODS = ['where', 'andwhere', 'orwhere'];

    /** @var list<string> */
    private const CONJUNCTION_OPERATORS = ['AND', 'OR', 'NOT'];

    /** @var array<string, array{exact: int}|array{atLeast: int}> */
    private const OPERAND_REQUIREMENTS = [
        'AND' => ['atLeast' => 1],
        'OR' => ['atLeast' => 1],
        'NOT' => ['exact' => 1],
        'BETWEEN' => ['atLeast' => 3],
        'NOT BETWEEN' => ['atLeast' => 3],
        'IN' => ['atLeast' => 2],
        'NOT IN' => ['atLeast' => 2],
        'LIKE' => ['atLeast' => 2],
        'NOT LIKE' => ['atLeast' => 2],
        'OR LIKE' => ['atLeast' => 2],
        'OR NOT LIKE' => ['atLeast' => 2],
        'EXISTS' => ['atLeast' => 1],
        'NOT EXISTS' => ['atLeast' => 1],
        '=' => ['exact' => 2],
        '!=' => ['exact' => 2],
        '<>' => ['exact' => 2],
        '>' => ['exact' => 2],
        '>=' => ['exact' => 2],
        '<' => ['exact' => 2],
        '<=' => ['exact' => 2],
    ];

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
        return MethodCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || !in_array(strtolower($node->name->name), self::METHODS, true)) {
            return [];
        }

        if (!$this->queryAnalyzer->isQueryExpression($node->var, $scope)) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        if (!$node->args[0]->value instanceof Array_) {
            return [];
        }

        return $this->validateConditionArray($node->args[0]->value, $node->name->name, $scope);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateConditionArray(Array_ $array, string $methodName, Scope $scope): array
    {
        if (!isset($array->items[0]) || $array->items[0]->unpack || $array->items[0]->key !== null) {
            return [];
        }

        foreach ($array->items as $item) {
            if ($item->unpack) {
                return [];
            }
        }

        $operatorName = $this->expressionValueResolver->getSingleStringValue($array->items[0]->value, $scope);
        if ($operatorName === null) {
            return [];
        }

        $operator = strtoupper($operatorName);
        $requirement = self::OPERAND_REQUIREMENTS[$operator] ?? null;
        if ($requirement === null) {
            return [];
        }

        $operandItems = array_slice($array->items, 1);
        $errors = $this->validateOperandCount(
            $operator,
            $requirement,
            count($operandItems),
            $methodName,
            $array
        );

        if (!in_array($operator, self::CONJUNCTION_OPERATORS, true)) {
            return $errors;
        }

        foreach ($operandItems as $operandItem) {
            if ($operandItem->value instanceof Array_) {
                $errors = array_merge($errors, $this->validateConditionArray(
                    $operandItem->value,
                    $methodName,
                    $scope
                ));
            }
        }

        return $errors;
    }

    /**
     * @param array{exact: int}|array{atLeast: int} $requirement
     *
     * @return list<IdentifierRuleError>
     */
    private function validateOperandCount(
        string $operator,
        array $requirement,
        int $actualCount,
        string $methodName,
        Array_ $array
    ): array {
        if (isset($requirement['exact']) && $actualCount !== $requirement['exact']) {
            return [$this->buildError($operator, $methodName, 'exactly', $requirement['exact'], $actualCount, $array)];
        }

        if (isset($requirement['atLeast']) && $actualCount < $requirement['atLeast']) {
            return [$this->buildError($operator, $methodName, 'at least', $requirement['atLeast'], $actualCount, $array)];
        }

        return [];
    }

    private function buildError(
        string $operator,
        string $methodName,
        string $quantifier,
        int $expectedCount,
        int $actualCount,
        Array_ $array
    ): IdentifierRuleError {
        return ErrorBuilder::build(
            sprintf(
                'Operator \'%s\' in %s() requires %s %d operand%s, %d given.',
                $operator,
                $methodName,
                $quantifier,
                $expectedCount,
                $expectedCount === 1 ? '' : 's',
                $actualCount
            ),
            Identifiers::QUERY_CONDITION_VALIDATION,
            $array->getStartLine()
        );
    }
}
