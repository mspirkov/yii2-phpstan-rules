<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoComplexControllerActionsRule;

/**
 * @extends AbstractTestCase<NoComplexControllerActionsRule>
 */
final class NoComplexControllerActionsRuleCustomConfigTest extends AbstractTestCase
{
    public function testCustomConfiguration(): void
    {
        $this->analyse(
            [self::getDataFilePath('custom-config')],
            [],
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            self::getConfigFilePath('config'),
        ]);
    }

    protected static function getRuleClass(): string
    {
        return NoComplexControllerActionsRule::class;
    }
}
