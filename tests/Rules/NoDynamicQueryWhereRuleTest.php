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
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 16],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 18],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 20],
                ['Dynamic string conditions in Query::andWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 28],
                ['Dynamic string conditions in Query::orWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 30],
                ['Dynamic string conditions in Query::andWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 32],
                ['Dynamic string conditions in Query::orWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 34],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 53],
                ['Dynamic string conditions in Query::andWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 55],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 61],
                ['Dynamic string conditions in Query::andWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 63],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 69],
                ['Dynamic string conditions in Query::orWhere() are forbidden. Use array condition syntax, for example [\'column\' => $columnValue].', 71],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoDynamicQueryWhereRule::class;
    }
}
