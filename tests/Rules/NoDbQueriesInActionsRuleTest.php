<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDbQueriesInActionsRule;

/**
 * @extends AbstractTestCase<NoDbQueriesInActionsRule>
 */
final class NoDbQueriesInActionsRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Database queries in actions are forbidden. Move queries to repositories.', 13],
                ['Database queries in actions are forbidden. Move queries to repositories.', 15],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoDbQueriesInActionsRule::class;
    }
}
