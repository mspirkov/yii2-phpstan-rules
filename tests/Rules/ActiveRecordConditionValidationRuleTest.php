<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ActiveRecordConditionValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation\Customer;

/**
 * @extends AbstractTestCase<ActiveRecordConditionValidationRule>
 */
final class ActiveRecordConditionValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $customerClass = Customer::class;

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in findOne() condition.', $customerClass), 25],
                [sprintf('Value for attribute "status" on ActiveRecord %s in findOne() condition must be int, string given.', $customerClass), 26],
                [sprintf('Value for attribute "status" on ActiveRecord %s in findOne() condition must be int, string given.', $customerClass), 27],
                [sprintf('Unknown attribute "emial" for ActiveRecord %s in findAll() condition.', $customerClass), 28],
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in deleteAll() condition.', $customerClass), 29],
                [sprintf('Unknown attribute "idd" for ActiveRecord %s in updateAll() condition.', $customerClass), 30],
                [sprintf('Unknown attribute "statuss" for ActiveRecord %s in updateAllCounters() condition.', $customerClass), 31],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ActiveRecordConditionValidationRule::class;
    }
}
