<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoComplexControllerActionsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoComplexControllerActionsRule>
 */
final class NoComplexControllerActionsRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/NoComplexActions/controller-actions.php'],
            [
                ['Controller action contains too much business logic: ifCount is 5, allowed 3. Move business logic to the service layer.', 30],
                ['Controller action contains too much business logic: foreachCount is 1, allowed 0. Move business logic to the service layer.', 39],
                ['Controller action contains too much business logic: forCount is 1, allowed 0. Move business logic to the service layer.', 42],
                ['Controller action contains too much business logic: whileCount is 1, allowed 0. Move business logic to the service layer.', 45],
                ['Controller action contains too much business logic: doWhileCount is 1, allowed 0. Move business logic to the service layer.', 48],
                ['Controller action contains too much business logic: switchCount is 1, allowed 0. Move business logic to the service layer.', 51],
                ['Controller action contains too much business logic: matchCount is 1, allowed 0. Move business logic to the service layer.', 56],
            ],
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/Config/NoComplexActions/default.neon',
        ]);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoComplexControllerActionsRule::class);
    }
}
