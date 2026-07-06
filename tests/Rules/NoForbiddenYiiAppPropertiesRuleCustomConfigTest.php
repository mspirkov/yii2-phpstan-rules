<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoForbiddenYiiAppPropertiesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoForbiddenYiiAppPropertiesRule>
 */
final class NoForbiddenYiiAppPropertiesRuleCustomConfigTest extends RuleTestCase
{
    public function testCustomConfiguration(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/NoForbiddenYiiAppProperties/code.php'],
            [
                ['Use of Yii::$app->request is forbidden.', 9],
                ['Use of dynamic Yii::$app property is forbidden.', 12],
                ['Use of Yii::$app->request is forbidden.', 14],
            ],
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/Config/NoForbiddenYiiAppProperties/config.neon',
        ]);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoForbiddenYiiAppPropertiesRule::class);
    }
}
