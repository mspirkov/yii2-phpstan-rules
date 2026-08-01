<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ActiveRecordUpdateValuesValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\Customer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\NotActiveRecord;
use yii\db\Expression;

final class ValidCustomerUsage
{
    public function run(): void
    {
        Customer::updateAll(['status' => 1], ['id' => 5]);
        Customer::updateAllCounters(['age' => 1]);
    }
}

final class InvalidCustomerUsage
{
    public function run(): void
    {
        Customer::updateAll(['statuss' => 1], ['id' => 5]);
        Customer::updateAll(['status' => 'active']);
        Customer::updateAll(['status' => [1, 2, 3]]);
        Customer::updateAllCounters(['agee' => 1]);
        Customer::updateAllCounters(['age' => 'one']);
    }
}

final class SkippedCustomerUsage
{
    public function run(): void
    {
        // Not a checked method.
        Customer::findOne(['status' => 1]);

        // No attribute-values argument at all.
        Customer::updateAll();

        // Values built dynamically — not an array literal.
        $attributes = ['status' => 1];
        Customer::updateAll($attributes);

        // Not an ActiveRecord at all.
        NotActiveRecord::updateAll(['status' => 1]);

        // Dynamic class / dynamic method name — not a plain Name / Identifier.
        $className = Customer::class;
        $className::updateAll(['statuss' => 1]);
        $methodName = 'updateAll';
        Customer::$methodName(['statuss' => 1]);

        // ExpressionInterface bypasses dbTypecast in QueryBuilder::prepareUpdateSets(), so
        // it's valid for any attribute regardless of its declared type.
        Customer::updateAll(['updated_at' => new Expression('NOW()')]);
    }
}
