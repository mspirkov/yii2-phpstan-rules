<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoComplexActionClassesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoComplexActionClassesRule>
 */
final class NoComplexActionClassesRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/NoComplexActions/action-classes.php'],
            [
                ['Action class contains too much business logic: ifCount is 4, allowed 3. Move business logic to the service layer.', 20],
                ['Action class contains too much business logic: foreachCount is 1, allowed 0. Move business logic to the service layer.', 23],
            ],
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/config/NoComplexActions/default.neon',
        ]);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoComplexActionClassesRule::class);
    }
}
