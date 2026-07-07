<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDynamicQueryWhereRule;

/**
 * @extends AbstractTestCase<NoDynamicQueryWhereRule>
 */
final class NoDynamicQueryWhereRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 11],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 13],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 15],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 38],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoDynamicQueryWhereRule::class;
    }
}
