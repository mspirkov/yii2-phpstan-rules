<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDbQueriesInViewsRule;

/**
 * @extends AbstractTestCase<NoDbQueriesInViewsRule>
 */
final class NoDbQueriesInViewsRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('views/site/index')],
            [
                ['Database queries in views are forbidden. Move queries to repositories.', 16],
                ['Database queries in views are forbidden. Move queries to repositories.', 18],
                ['Database queries in views are forbidden. Move queries to repositories.', 20],
                ['Database queries in views are forbidden. Move queries to repositories.', 22],
                ['Database queries in views are forbidden. Move queries to repositories.', 24],
                ['Database queries in views are forbidden. Move queries to repositories.', 25],
                ['Database queries in views are forbidden. Move queries to repositories.', 27],
                ['Database queries in views are forbidden. Move queries to repositories.', 41],
                ['Database queries in views are forbidden. Move queries to repositories.', 42],
                ['Database queries in views are forbidden. Move queries to repositories.', 43],
                ['Database queries in views are forbidden. Move queries to repositories.', 45],
                ['Database queries in views are forbidden. Move queries to repositories.', 46],
                ['Database queries in views are forbidden. Move queries to repositories.', 62],
                ['Database queries in views are forbidden. Move queries to repositories.', 65],
                ['Database queries in views are forbidden. Move queries to repositories.', 70],
                ['Database queries in views are forbidden. Move queries to repositories.', 72],
                ['Database queries in views are forbidden. Move queries to repositories.', 73],
                ['Database queries in views are forbidden. Move queries to repositories.', 75],
                ['Database queries in views are forbidden. Move queries to repositories.', 77],
                ['Database queries in views are forbidden. Move queries to repositories.', 78],
                ['Database queries in views are forbidden. Move queries to repositories.', 80],
            ],
        );
    }

    public function testRuleSkipsNonViewFiles(): void
    {
        $this->analyse(
            [self::getDataFilePath('not-view')],
            [],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoDbQueriesInViewsRule::class;
    }
}
