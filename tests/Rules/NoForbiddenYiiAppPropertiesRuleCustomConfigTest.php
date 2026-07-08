<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoForbiddenYiiAppPropertiesRule;

/**
 * @extends AbstractTestCase<NoForbiddenYiiAppPropertiesRule>
 */
final class NoForbiddenYiiAppPropertiesRuleCustomConfigTest extends AbstractTestCase
{
    public function testCustomConfiguration(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Use of Yii application property "request" is forbidden.', 9],
                ['Use of dynamic Yii application property is forbidden.', 12],
                ['Use of Yii application property "request" is forbidden.', 14],
                ['Use of Yii application property "request" is forbidden.', 22],
                ['Use of Yii application property "request" is forbidden.', 29],
                ['Use of dynamic Yii application property is forbidden.', 30],
            ],
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
        return NoForbiddenYiiAppPropertiesRule::class;
    }
}
