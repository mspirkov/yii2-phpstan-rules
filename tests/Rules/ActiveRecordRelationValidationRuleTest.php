<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ActiveRecordRelationValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation\Country;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation\Order;

/**
 * @extends AbstractTestCase<ActiveRecordRelationValidationRule>
 */
final class ActiveRecordRelationValidationRuleTest extends AbstractTestCase
{
    private const CUSTOMER_CLASS = 'MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ActiveRecordRelationValidation\Customer';

    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown property "missing_id" for related ActiveRecord %s in hasOne() relation link.', Country::class), 48],
                [sprintf('Unknown property "missing_country_id" for current ActiveRecord %s in hasOne() relation link.', self::CUSTOMER_CLASS), 49],
                [sprintf('Unknown property "missing_customer_id" for related ActiveRecord %s in hasMany() relation link.', Order::class), 50],
                [sprintf('Unknown property "missing_id" for current ActiveRecord %s in hasMany() relation link.', self::CUSTOMER_CLASS), 50],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ActiveRecordRelationValidationRule::class;
    }
}
