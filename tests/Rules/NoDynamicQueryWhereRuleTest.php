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
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 14],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 16],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 18],
                ['Dynamic string conditions in Query::andWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 26],
                ['Dynamic string conditions in Query::orWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 28],
                ['Dynamic string conditions in Query::andWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 30],
                ['Dynamic string conditions in Query::orWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 32],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 51],
                ['Dynamic string conditions in Query::andWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 53],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoDynamicQueryWhereRule::class;
    }
}
