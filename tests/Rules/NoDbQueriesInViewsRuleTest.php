<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDbQueriesInViewsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoDbQueriesInViewsRule>
 */
final class NoDbQueriesInViewsRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/NoDbQueriesInViews/views/site/index.php'],
            [
                ['Database queries in views are forbidden. Move queries to repositories.', 13],
                ['Database queries in views are forbidden. Move queries to repositories.', 15],
                ['Database queries in views are forbidden. Move queries to repositories.', 17],
                ['Database queries in views are forbidden. Move queries to repositories.', 19],
                ['Database queries in views are forbidden. Move queries to repositories.', 21],
                ['Database queries in views are forbidden. Move queries to repositories.', 22],
                ['Database queries in views are forbidden. Move queries to repositories.', 24],
                ['Database queries in views are forbidden. Move queries to repositories.', 38],
                ['Database queries in views are forbidden. Move queries to repositories.', 39],
                ['Database queries in views are forbidden. Move queries to repositories.', 40],
                ['Database queries in views are forbidden. Move queries to repositories.', 42],
                ['Database queries in views are forbidden. Move queries to repositories.', 43],
                ['Database queries in views are forbidden. Move queries to repositories.', 59],
                ['Database queries in views are forbidden. Move queries to repositories.', 62],
            ],
        );
    }

    public function testRuleSkipsNonViewFiles(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/NoDbQueriesInViews/not-view.php'],
            [],
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
        return self::getContainer()->getByType(NoDbQueriesInViewsRule::class);
    }
}
