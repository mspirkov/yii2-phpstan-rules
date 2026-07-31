<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\db\ActiveQueryInterface;
use yii\db\QueryInterface;

/**
 * @implements Rule<MethodCall>
 */
final class QueryConditionValidationRule implements Rule
{
    /** @var list<string> */
    private const METHODS = ['where', 'andwhere', 'orwhere'];

    /** @var list<class-string> */
    private const QUERY_CLASSES = [
        ActiveQueryInterface::class,
        QueryInterface::class,
    ];

    /**
     * Operators that combine nested conditions: their operands are recursed into, in addition to
     * being checked against the operand-count rule below.
     *
     * @var list<string>
     */
    private const CONJUNCTION_OPERATORS = ['AND', 'OR', 'NOT'];

    /**
     * Minimum/exact operand counts mirroring each condition class's own fromArrayDefinition()
     * check (see yii\db\conditions\*). "and"/"or" are not validated by
     * yii\db\conditions\ConjunctionCondition itself, but an empty one can never produce a
     * meaningful condition, so at least one operand is still required. The comparison operators
     * (`=`, `>=`, ...) are Yii's documented "arbitrary operator" case, all falling back to
     * yii\db\conditions\SimpleCondition, which requires exactly 2 operands. Any operator not
     * listed here — a genuinely custom one registered via QueryBuilder::setConditionClasses() —
     * is left unchecked rather than guessed at.
     *
     * @var array<string, array{exact: int}|array{atLeast: int}>
     */
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

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    public function __construct(
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
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

        if (!$this->expressionTypeAnalyzer->isTypeAnyOf($scope->getType($node->var), self::QUERY_CLASSES)) {
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
        /** @var list<ArrayItem> $operandItems */
        $operandItems = array_slice($array->items, 1);

        if (!isset(self::OPERAND_REQUIREMENTS[$operator])) {
            return [];
        }

        $errors = $this->validateOperandCount(
            $operator,
            self::OPERAND_REQUIREMENTS[$operator],
            count($operandItems),
            $methodName,
            $array
        );

        if (!in_array($operator, self::CONJUNCTION_OPERATORS, true)) {
            return $errors;
        }

        foreach ($operandItems as $operandItem) {
            if ($operandItem->value instanceof Array_) {
                $errors = array_merge($errors, $this->validateConditionArray($operandItem->value, $methodName, $scope));
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
