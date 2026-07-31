<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\QueryConditionValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\QueryConditionValidation\NotQuery;
use yii\db\Query;

final class ValidQueryUsage
{
    public function run(Query $query): void
    {
        $query->where(['in', 'status', [1, 2]]);
        $query->andWhere(['not in', 'status', [1, 2]]);
        $query->orWhere(['between', 'age', 18, 65]);
        $query->where(['not between', 'age', 18, 65]);
        $query->where(['like', 'name', 'foo']);
        $query->where(['like', 'name', 'foo', false]);
        $query->where(['not like', 'name', 'foo']);
        $query->where(['or like', 'name', 'foo']);
        $query->where(['or not like', 'name', 'foo']);
        $query->where(['not', ['status' => 1]]);
        $query->where(['exists', 'SELECT 1']);
        $query->where(['not exists', 'SELECT 1']);
        $query->where(['and', ['status' => 1], ['in', 'type', [1, 2]]]);
        $query->where(['or', ['status' => 1], ['type' => 2]]);
        $query->where(['>=', 'age', 18]);
        $query->where(['status' => 1]);
    }
}

final class InvalidQueryUsage
{
    public function run(Query $query): void
    {
        $query->where(['in', 'status']);
        $query->andWhere(['between', 'age', 18]);
        $query->orWhere(['not', 'a', 'b']);
        $query->where(['like', 'name']);
        $query->where(['exists']);
        $query->where(['>=', 'age', 18, 30]);
        $query->where(['not', ['in', 'status']]);
        $query->where(['and']);
    }
}

final class SkippedQueryUsage
{
    public function run(Query $query, string $dynamicOperator): void
    {
        // Not a checked method.
        $query->select('id');

        // Not a Query/ActiveQuery type.
        (new NotQuery())->where(['in', 'status']);

        // Missing the condition argument.
        $query->where();

        // Condition built dynamically — not an array literal.
        $condition = ['in', 'status'];
        $query->where($condition);

        // Hash-format condition — never operator format.
        $query->where(['status' => 1]);

        // Operator isn't a resolvable single string constant.
        $query->where([$dynamicOperator, 'status', 1]);

        // A genuinely custom operator (e.g. registered via setConditionClasses()) isn't in the
        // recognized map — left unchecked rather than guessed at.
        $query->where(['json_contains', 'tags', 'foo']);

        // Unpacked items — can't reliably count operands.
        $query->where([...['in', 'status']]);
        $rest = ['status', [1, 2]];
        $query->where(['in', ...$rest]);

        // "and" with raw string sub-conditions — nothing to recurse into.
        $query->where(['and', 'status = 1', 'type = 2']);

        // Dynamic method name — not a plain Identifier.
        $methodName = 'where';
        $query->$methodName(['in', 'status']);
    }
}
