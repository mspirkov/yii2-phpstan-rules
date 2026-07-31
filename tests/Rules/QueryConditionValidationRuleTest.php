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
                ["Operator 'IN' in where() requires at least 2 operands, 1 given.", 35],
                ["Operator 'BETWEEN' in andWhere() requires at least 3 operands, 2 given.", 36],
                ["Operator 'NOT' in orWhere() requires exactly 1 operand, 2 given.", 37],
                ["Operator 'LIKE' in where() requires at least 2 operands, 1 given.", 38],
                ["Operator 'EXISTS' in where() requires at least 1 operand, 0 given.", 39],
                ["Operator '>=' in where() requires exactly 2 operands, 3 given.", 40],
                ["Operator 'IN' in where() requires at least 2 operands, 1 given.", 41],
                ["Operator 'AND' in where() requires at least 1 operand, 0 given.", 42],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return QueryConditionValidationRule::class;
    }
}
