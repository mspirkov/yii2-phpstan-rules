<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDbQueriesInViewsRule;

/**
 * @extends AbstractTestCase<NoDbQueriesInViewsRule>
 */
final class NoDbQueriesInViewsRuleCustomDbPropertiesTest extends AbstractTestCase
{
    public function testRuleUsesCustomYiiAppDbProperties(): void
    {
        $this->analyse(
            [self::getDataFilePath('/views/site/custom-db-properties')],
            [
                ['Database queries in views are forbidden. Move queries to repositories.', 3],
                ['Database queries in views are forbidden. Move queries to repositories.', 5],
                ['Database queries in views are forbidden. Move queries to repositories.', 7],
            ],
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            self::getConfigFilePath('config'),
        ]);
    }

    protected static function getRuleClass(): string
    {
        return NoDbQueriesInViewsRule::class;
    }
}
