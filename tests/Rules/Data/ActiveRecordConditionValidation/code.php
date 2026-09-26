<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ActiveRecordConditionValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation\Customer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation\MongoCustomer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation\NotActiveRecord;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation\RedisCustomer;
use yii\db\Expression;

final class ValidCustomerUsage
{
    public function run(): void
    {
        Customer::findOne(['id' => 5]);
        Customer::findOne(['status' => [1, 2, 3]]);
        Customer::findAll(['email' => 'name@example.com']);
        Customer::deleteAll(['status' => 1]);
        Customer::updateAll(['status' => 1], ['id' => 5]);
        Customer::updateAllCounters(['age' => 1], ['id' => 5]);
    }

    // yii\mongodb\ActiveRecord extends yii\db\BaseActiveRecord, so this rule applies to it unchanged.
    public function runMongo(): void
    {
        MongoCustomer::findOne(['status' => 1]);
    }

    // yii\redis\ActiveRecord extends yii\db\BaseActiveRecord, so this rule applies to it unchanged.
    public function runRedis(): void
    {
        RedisCustomer::findOne(['status' => 1]);
    }
}

final class InvalidCustomerUsage
{
    public function run(): void
    {
        Customer::findOne(['statuss' => 1]);
        Customer::findOne(['status' => '1']);
        Customer::findOne(['status' => ['bad', 'values']]);
        Customer::findAll(['emial' => 'name@example.com']);
        Customer::deleteAll(['statuss' => 1]);
        Customer::updateAll(['status' => 1], ['idd' => 5]);
        Customer::updateAllCounters(['age' => 1], ['statuss' => 1]);
    }

    public function runMongo(): void
    {
        MongoCustomer::findOne(['statuss' => 1]);
        MongoCustomer::findOne(['status' => '1']);
    }

    public function runRedis(): void
    {
        RedisCustomer::findOne(['statuss' => 1]);
        RedisCustomer::findOne(['status' => '1']);
    }
}

final class SkippedCustomerUsage
{
    public function run(): void
    {
        // Scalar / list-style condition — a primary key lookup, not an attribute hash.
        Customer::findOne(5);
        Customer::findOne([1, 2, 3]);
        Customer::deleteAll();

        // Spread items carry no resolvable key.
        Customer::findOne([...['id' => 'not-an-int']]);

        // Known attribute, but only through a getter/setter pair — no declared/PHPDoc
        // property to read a type from, so the value type is left unchecked.
        Customer::findOne(['fullName' => 123]);

        // Known attribute, but read-only (@property-read) — nothing is ever written to it.
        Customer::findOne(['displayName' => 123]);

        // Does not look like a plain attribute name (dotted relation path).
        Customer::findOne(['customer.status' => 1]);

        // Condition built dynamically — not an array literal.
        $dynamicCondition = ['status' => 1];
        Customer::findOne($dynamicCondition);

        // Key isn't a resolvable single string constant.
        $this->findByDynamicKey('id');

        // Not an ActiveRecord at all.
        NotActiveRecord::findOne(['status' => 1]);

        // Not a checked method.
        Customer::find();

        // Dynamic class / dynamic method name — not a plain Name / Identifier.
        $className = Customer::class;
        $className::findOne(['statuss' => 1]);
        $methodName = 'findOne';
        Customer::$methodName(['statuss' => 1]);

        // Attribute type is mixed — nothing meaningful to compare against.
        Customer::findOne(['extra' => 5]);

        // ExpressionInterface bypasses dbTypecast in HashConditionBuilder::build(), so it's
        // valid for any attribute regardless of its declared type.
        Customer::findOne(['updated_at' => new Expression('NOW()')]);

        // Same bypass applies per-value inside an IN condition — e.g. matching either
        // a dynamically configured default status or one of two explicit ones.
        Customer::findOne(['status' => [new Expression('(SELECT default_status FROM settings)'), 1, 2]]);
    }

    private function findByDynamicKey(string $dynamicKey): void
    {
        Customer::findOne([$dynamicKey => 5]);
    }
}
