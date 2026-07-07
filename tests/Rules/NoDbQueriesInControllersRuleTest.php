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
                ['Database queries in controllers are forbidden. Move queries to repositories.', 13],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 15],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 17],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 19],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 21],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 28],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoDbQueriesInControllersRule::class;
    }
}
