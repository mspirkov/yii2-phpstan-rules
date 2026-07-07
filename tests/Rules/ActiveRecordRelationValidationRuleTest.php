<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ActiveRecordRelationValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation\Country;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation\Order;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ActiveRecordRelationValidationRule>
 */
final class ActiveRecordRelationValidationRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $countryClass = Country::class;
        $customerClass = 'MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ActiveRecordRelationValidation\Customer';
        $orderClass = Order::class;

        $this->analyse(
            [__DIR__ . '/Data/ActiveRecordRelationValidation/code.php'],
            [
                [sprintf('Unknown property "missing_id" for related ActiveRecord %s in hasOne() relation link.', $countryClass), 46],
                [sprintf('Unknown property "missing_country_id" for current ActiveRecord %s in hasOne() relation link.', $customerClass), 47],
                [sprintf('Unknown property "missing_customer_id" for related ActiveRecord %s in hasMany() relation link.', $orderClass), 48],
                [sprintf('Unknown property "missing_id" for current ActiveRecord %s in hasMany() relation link.', $customerClass), 48],
                ['Unknown related ActiveRecord class "MissingRecord" in hasOne() relation.', 52],
            ],
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../../rules.neon',
        ]);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ActiveRecordRelationValidationRule::class);
    }
}
