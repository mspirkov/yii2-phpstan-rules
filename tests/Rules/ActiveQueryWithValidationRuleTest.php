<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ActiveQueryWithValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation\Customer;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation\Order;

/**
 * @extends AbstractTestCase<ActiveQueryWithValidationRule>
 */
final class ActiveQueryWithValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown relation "bogus" for %s in with() call.', Customer::class), 54],
                [sprintf('Unknown relation "displayName" for %s in with() call.', Customer::class), 59],
                [sprintf('Unknown relation "bogus" for %s in with() call.', Order::class), 64],
                [sprintf('Unknown relation "bogus" for %s in with() call.', Customer::class), 69],
                [sprintf('Unknown relation "bogus" for %s in joinWith() call.', Customer::class), 75],
                [sprintf('Unknown relation "bogus" for %s in with() call.', Customer::class), 80],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ActiveQueryWithValidationRule::class;
    }
}
