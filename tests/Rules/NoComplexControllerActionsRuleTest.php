<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoComplexControllerActionsRule;

/**
 * @extends AbstractTestCase<NoComplexControllerActionsRule>
 */
final class NoComplexControllerActionsRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('base')],
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

    protected static function getRuleClass(): string
    {
        return NoComplexControllerActionsRule::class;
    }
}
