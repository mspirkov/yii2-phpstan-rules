<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDynamicQueryWhereRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoDynamicQueryWhereRule>
 */
final class NoDynamicQueryWhereRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoDynamicQueryWhere/code.php'],
            [
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example ["column" => $columnValue].', 11],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example ["column" => $columnValue].', 13],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example ["column" => $columnValue].', 15],
                ['Dynamic string conditions in Query::where() are forbidden. Use array condition syntax, for example ["column" => $columnValue].', 38],
            ],
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
        return self::getContainer()->getByType(NoDynamicQueryWhereRule::class);
    }
}
