<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ActiveRecordConditionValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation\Customer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation\MongoCustomer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation\RedisCustomer;

/**
 * @extends AbstractTestCase<ActiveRecordConditionValidationRule>
 */
final class ActiveRecordConditionValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $customerClass = Customer::class;
        $mongoCustomerClass = MongoCustomer::class;
        $redisCustomerClass = RedisCustomer::class;

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in findOne() condition.', $customerClass), 40],
                [sprintf('Value for attribute "status" on ActiveRecord %s in findOne() condition must be int, string given.', $customerClass), 41],
                [sprintf('Value for attribute "status" on ActiveRecord %s in findOne() condition must be int, string given.', $customerClass), 42],
                [sprintf('Unknown attribute "emial" for ActiveRecord %s in findAll() condition.', $customerClass), 43],
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in deleteAll() condition.', $customerClass), 44],
                [sprintf('Unknown attribute "idd" for ActiveRecord %s in updateAll() condition.', $customerClass), 45],
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in updateAllCounters() condition.', $customerClass), 46],
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in findOne() condition.', $mongoCustomerClass), 51],
                [sprintf('Value for attribute "status" on ActiveRecord %s in findOne() condition must be int, string given.', $mongoCustomerClass), 52],
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in findOne() condition.', $redisCustomerClass), 57],
                [sprintf('Value for attribute "status" on ActiveRecord %s in findOne() condition must be int, string given.', $redisCustomerClass), 58],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ActiveRecordConditionValidationRule::class;
    }
}
