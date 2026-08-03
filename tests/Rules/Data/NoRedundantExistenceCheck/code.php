<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoRedundantExistenceCheck;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\NoRedundantExistenceCheck\NotQuery;
use yii\db\ActiveRecord;

final class ValidExistenceChecks
{
    public function run(): void
    {
        $activeQuery = ActiveRecord::find();

        // exists() is already the recommended call
        $activeQuery->where(['status' => 1])->exists();
        !$activeQuery->where(['status' => 1])->exists();

        // the row is fetched because it is actually needed afterwards, not just to test for null
        $record = $activeQuery->where(['status' => 1])->one();
        $record !== null;

        // the count is actually needed as a number, not just compared against 0/1
        $total = $activeQuery->where(['status' => 1])->count();
        $total > 10;
    }
}

final class InvalidExistenceChecks
{
    public function run(): void
    {
        $activeQuery = ActiveRecord::find();

        $activeQuery->where(['status' => 1])->one() !== null;

        null !== $activeQuery->where(['status' => 1])->one();

        $activeQuery->where(['status' => 1])->one() === null;

        null === $activeQuery->where(['status' => 1])->one();

        $activeQuery->where(['status' => 1])->count() > 0;

        0 < $activeQuery->where(['status' => 1])->count();

        $activeQuery->where(['status' => 1])->count() !== 0;

        0 !== $activeQuery->where(['status' => 1])->count();

        $activeQuery->where(['status' => 1])->count() < 1;

        1 > $activeQuery->where(['status' => 1])->count();

        $activeQuery->where(['status' => 1])->count() === 0;

        0 === $activeQuery->where(['status' => 1])->count();
    }
}

final class SkippedExistenceChecks
{
    public function run(): void
    {
        $activeQuery = ActiveRecord::find();

        // loose comparisons are not covered by this rule
        $activeQuery->where(['status' => 1])->count() == 0;
        $activeQuery->where(['status' => 1])->one() != null;

        // neither side is a one()/count() call on a query
        1 === 2;

        // one() compared to something other than null
        $activeQuery->where(['status' => 1])->one() !== $activeQuery;

        // count() compared to a value other than 0/1
        $activeQuery->where(['status' => 1])->count() !== 5;
        $activeQuery->where(['status' => 1])->count() > 5;
        5 > $activeQuery->where(['status' => 1])->count();

        // wrong method name
        $activeQuery->where(['status' => 1])->all() !== null;

        // dynamic method name can't be resolved statically
        $methodName = 'one';
        $activeQuery->where(['status' => 1])->{$methodName}() !== null;

        // not a Query/ActiveQuery at all, despite matching method names
        $notAQuery = new NotQuery();
        $notAQuery->one() !== null;
        $notAQuery->count() !== 0;

        // count() as a global function call, not a method call
        $items = [];
        count($items) > 0;
    }
}
