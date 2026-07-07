<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoComplexActionClassesRule;

/**
 * @extends AbstractTestCase<NoComplexActionClassesRule>
 */
final class NoComplexActionClassesRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Action class contains too much business logic: ifCount is 4, allowed 3. Move business logic to the service layer.', 20],
                ['Action class contains too much business logic: foreachCount is 1, allowed 0. Move business logic to the service layer.', 23],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoComplexActionClassesRule::class;
    }
}
