<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoDbQueriesInControllersRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoDbQueriesInControllersRule>
 */
final class NoDbQueriesInControllersRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/NoDbQueriesInApplicationClasses/code.php'],
            [
                ['Database queries in controllers are forbidden. Move queries to repositories.', 14],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 16],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 18],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 20],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 22],
                ['Database queries in controllers are forbidden. Move queries to repositories.', 29],
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
        return self::getContainer()->getByType(NoDbQueriesInControllersRule::class);
    }
}
