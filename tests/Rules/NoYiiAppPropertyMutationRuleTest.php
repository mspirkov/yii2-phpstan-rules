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
                ['Modification of Yii application property "name" is forbidden.', 6],
                ['Modification of Yii application property "language" is forbidden.', 8],
                ['Modification of Yii application property "params" is forbidden.', 10],
                ['Modification of Yii application property "counter" is forbidden.', 12],
                ['Modification of Yii application property "counter" is forbidden.', 13],
                ['Modification of Yii application property "db" is forbidden.', 16],
                ['Modification of Yii application property "cache" is forbidden.', 18],
                ['Modification of dynamic Yii application property is forbidden.', 21],
                ['Call to Yii application method "setComponents()" is forbidden because it modifies application properties.', 23],
                ['Call to Yii application method "setComponents()" is forbidden because it modifies application properties.', 25],
                ['Modification of Yii application property "name" is forbidden.', 39],
                ['Modification of Yii application property "counter" is forbidden.', 40],
                ['Modification of Yii application property "cache" is forbidden.', 41],
                ['Call to Yii application method "setComponents()" is forbidden because it modifies application properties.', 42],
                ['Modification of Yii application property "language" is forbidden.', 45],
                ['Modification of Yii application property "name" is forbidden.', 48],
                ['Modification of Yii application property "params" is forbidden.', 49],
                ['Modification of dynamic Yii application property is forbidden.', 50],
                ['Call to Yii application method "setComponents()" is forbidden because it modifies application properties.', 51],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoYiiAppPropertyMutationRule::class;
    }
}
