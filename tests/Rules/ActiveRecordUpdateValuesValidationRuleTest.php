<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ActiveRecordUpdateValuesValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation\Customer;

/**
 * @extends AbstractTestCase<ActiveRecordUpdateValuesValidationRule>
 */
final class ActiveRecordUpdateValuesValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $customerClass = Customer::class;

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in updateAll() attributes.', $customerClass), 21],
                [sprintf('Value for attribute "status" on ActiveRecord %s in updateAll() attributes must be int, string given.', $customerClass), 22],
                [sprintf('Value for attribute "status" on ActiveRecord %s in updateAll() attributes must be int, array<int, int> given.', $customerClass), 23],
                [sprintf('Unknown attribute "agee" for ActiveRecord %s in updateAllCounters() counters.', $customerClass), 24],
                [sprintf('Value for attribute "age" on ActiveRecord %s in updateAllCounters() counters must be int, string given.', $customerClass), 25],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ActiveRecordUpdateValuesValidationRule::class;
    }
}
