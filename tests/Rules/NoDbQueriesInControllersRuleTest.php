<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDbQueriesInControllersRule;

/**
 * @extends AbstractTestCase<NoDbQueriesInControllersRule>
 */
final class NoDbQueriesInControllersRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Database queries in controllers are forbidden. Move queries to repositories.', 20],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 22],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 24],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 26],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 28],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 35],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 40],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 42],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 44],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 49],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 51],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 56],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 61],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 69],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 70],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 71],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 76],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoDbQueriesInControllersRule::class;
    }
}
