<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoYiiAppPropertyMutationRule;

/**
 * @extends AbstractTestCase<NoYiiAppPropertyMutationRule>
 */
final class NoYiiAppPropertyMutationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Modification of Yii::$app->name is forbidden.', 6],
                ['Modification of Yii::$app->language is forbidden.', 8],
                ['Modification of Yii::$app->params is forbidden.', 10],
                ['Modification of Yii::$app->counter is forbidden.', 12],
                ['Modification of Yii::$app->counter is forbidden.', 13],
                ['Modification of Yii::$app->db is forbidden.', 16],
                ['Modification of Yii::$app->cache is forbidden.', 18],
                ['Modification of dynamic Yii::$app property is forbidden.', 21],
                ['Call to Yii::$app->setComponents() is forbidden because it modifies application properties.', 23],
                ['Call to Yii::$app->setComponents() is forbidden because it modifies application properties.', 25],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoYiiAppPropertyMutationRule::class;
    }
}
