<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\YiiCreateObjectValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\YiiCreateObjectValidation\CreatableComponent;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\YiiCreateObjectValidation\PlainObject;

/**
 * @extends AbstractTestCase<YiiCreateObjectValidationRule>
 */
final class YiiCreateObjectValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown option "unknownOption" for object %s.', CreatableComponent::class), 27],
                [sprintf('Object option "limit" for %s must be int, string given.', CreatableComponent::class), 35],
                ['Yii::createObject() configuration array must specify "class" or "__class".', 50],
                ['Yii::createObject() configuration option keys must be strings.', 66],
                ['Yii::createObject() configuration option keys must be strings.', 77],
                ['Yii::createObject() configuration option keys must be strings.', 77],
                ['Yii::createObject() configuration array must specify "class" or "__class".', 77],
                ['Yii::createObject() configuration array must specify "class" or "__class".', 84],
                ['Yii::createObject() configuration option keys must be strings.', 91],
                ['Yii::createObject() configuration option keys must be strings.', 91],
                ['Yii::createObject() configuration option keys must be strings.', 91],
                ['Yii::createObject() configuration array must specify "class" or "__class".', 91],
                [sprintf('Unknown option "unknownOption" for object %s.', PlainObject::class), 98],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return YiiCreateObjectValidationRule::class;
    }
}
