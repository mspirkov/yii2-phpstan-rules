<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoComplexControllerActionsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoComplexControllerActionsRule>
 */
final class NoComplexControllerActionsRuleCustomConfigTest extends RuleTestCase
{
    public function testCustomConfiguration(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/NoComplexActions/custom-config.php'],
            [],
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/Config/NoComplexActions/config.neon',
        ]);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoComplexControllerActionsRule::class);
    }
}
