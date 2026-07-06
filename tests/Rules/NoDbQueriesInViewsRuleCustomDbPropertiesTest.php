<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDbQueriesInViewsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoDbQueriesInViewsRule>
 */
final class NoDbQueriesInViewsRuleCustomDbPropertiesTest extends RuleTestCase
{
    public function testRuleUsesCustomYiiAppDbProperties(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/NoDbQueriesInViews/views/site/custom-db-properties.php'],
            [
                ['Database queries in views are forbidden. Move queries to repositories.', 3],
                ['Database queries in views are forbidden. Move queries to repositories.', 5],
            ],
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/Config/NoDbQueriesInViews/custom-db-properties.neon',
        ]);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoDbQueriesInViewsRule::class);
    }
}
