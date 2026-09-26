<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ActiveRecordUpdateValuesValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\Customer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\MongoCustomer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\RedisCustomer;

/**
 * @extends AbstractTestCase<ActiveRecordUpdateValuesValidationRule>
 */
final class ActiveRecordUpdateValuesValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $customerClass = Customer::class;
        $mongoCustomerClass = MongoCustomer::class;
        $redisCustomerClass = RedisCustomer::class;

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in updateAll() attributes.', $customerClass), 36],
                [sprintf('Value for attribute "status" on ActiveRecord %s in updateAll() attributes must be int, string given.', $customerClass), 37],
                [sprintf('Value for attribute "status" on ActiveRecord %s in updateAll() attributes must be int, array<int, int> given.', $customerClass), 38],
                [sprintf('Unknown attribute "agee" for ActiveRecord %s in updateAllCounters() counters.', $customerClass), 39],
                [sprintf('Value for attribute "age" on ActiveRecord %s in updateAllCounters() counters must be int, string given.', $customerClass), 40],
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in updateAll() attributes.', $mongoCustomerClass), 45],
                [sprintf('Value for attribute "age" on ActiveRecord %s in updateAllCounters() counters must be int, string given.', $mongoCustomerClass), 46],
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in updateAll() attributes.', $redisCustomerClass), 51],
                [sprintf('Value for attribute "age" on ActiveRecord %s in updateAllCounters() counters must be int, string given.', $redisCustomerClass), 52],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ActiveRecordUpdateValuesValidationRule::class;
    }
}
