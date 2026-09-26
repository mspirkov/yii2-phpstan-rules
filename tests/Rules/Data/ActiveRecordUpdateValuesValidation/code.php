<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ActiveRecordUpdateValuesValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\Customer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\MongoCustomer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\NotActiveRecord;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\RedisCustomer;
use yii\db\Expression;

final class ValidCustomerUsage
{
    public function run(): void
    {
        Customer::updateAll(['status' => 1], ['id' => 5]);
        Customer::updateAllCounters(['age' => 1]);
    }

    // yii\mongodb\ActiveRecord extends yii\db\BaseActiveRecord, so this rule applies to it unchanged.
    public function runMongo(): void
    {
        MongoCustomer::updateAll(['status' => 1]);
    }

    // yii\redis\ActiveRecord extends yii\db\BaseActiveRecord, so this rule applies to it unchanged.
    public function runRedis(): void
    {
        RedisCustomer::updateAll(['status' => 1]);
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

    public function runMongo(): void
    {
        MongoCustomer::updateAll(['statuss' => 1]);
        MongoCustomer::updateAllCounters(['age' => 'one']);
    }

    public function runRedis(): void
    {
        RedisCustomer::updateAll(['statuss' => 1]);
        RedisCustomer::updateAllCounters(['age' => 'one']);
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
