<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoRedundantExistenceCheckRule;

/**
 * @extends AbstractTestCase<NoRedundantExistenceCheckRule>
 */
final class NoRedundantExistenceCheckRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Comparing the result of Query::one() only checks whether a matching row exists. Use Query::exists() instead.', 34],
                ['Comparing the result of Query::one() only checks whether a matching row exists. Use Query::exists() instead.', 36],
                ['Comparing the result of Query::one() only checks whether a matching row exists. Use !Query::exists() instead.', 38],
                ['Comparing the result of Query::one() only checks whether a matching row exists. Use !Query::exists() instead.', 40],
                ['Comparing the result of Query::count() only checks whether a matching row exists. Use Query::exists() instead.', 42],
                ['Comparing the result of Query::count() only checks whether a matching row exists. Use Query::exists() instead.', 44],
                ['Comparing the result of Query::count() only checks whether a matching row exists. Use Query::exists() instead.', 46],
                ['Comparing the result of Query::count() only checks whether a matching row exists. Use Query::exists() instead.', 48],
                ['Comparing the result of Query::count() only checks whether a matching row exists. Use !Query::exists() instead.', 50],
                ['Comparing the result of Query::count() only checks whether a matching row exists. Use !Query::exists() instead.', 52],
                ['Comparing the result of Query::count() only checks whether a matching row exists. Use !Query::exists() instead.', 54],
                ['Comparing the result of Query::count() only checks whether a matching row exists. Use !Query::exists() instead.', 56],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoRedundantExistenceCheckRule::class;
    }
}
