<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoForbiddenYiiAppPropertiesRule;

/**
 * @extends AbstractTestCase<NoForbiddenYiiAppPropertiesRule>
 */
final class NoForbiddenYiiAppPropertiesRuleTest extends AbstractTestCase
{
    public function testDefaultConfiguration(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Use of Yii::$app->request is forbidden.', 9],
                ['Use of dynamic Yii::$app property is forbidden.', 12],
                ['Use of Yii::$app->request is forbidden.', 14],
                ['Use of Yii::$app->db is forbidden.', 16],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoForbiddenYiiAppPropertiesRule::class;
    }
}
