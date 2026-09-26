<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\QueryConditionValidationRule;

/**
 * @extends AbstractTestCase<QueryConditionValidationRule>
 */
final class QueryConditionValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ["Operator 'IN' in where() requires at least 2 operands, 1 given.", 54],
                ["Operator 'BETWEEN' in andWhere() requires at least 3 operands, 2 given.", 55],
                ["Operator 'NOT' in orWhere() requires exactly 1 operand, 2 given.", 56],
                ["Operator 'LIKE' in where() requires at least 2 operands, 1 given.", 57],
                ["Operator 'EXISTS' in where() requires at least 1 operand, 0 given.", 58],
                ["Operator '>=' in where() requires exactly 2 operands, 3 given.", 59],
                ["Operator 'IN' in where() requires at least 2 operands, 1 given.", 60],
                ["Operator 'AND' in where() requires at least 1 operand, 0 given.", 61],
                ["Operator 'IN' in where() requires at least 2 operands, 1 given.", 66],
                ["Operator 'EXISTS' in where() requires at least 1 operand, 0 given.", 67],
                ["Operator 'BETWEEN' in where() requires at least 3 operands, 2 given.", 72],
                ["Operator 'NOT' in where() requires exactly 1 operand, 2 given.", 73],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return QueryConditionValidationRule::class;
    }
}
