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
                [sprintf('Object option "__construct()" for %s must be array, string given.', CreatableComponent::class), 61],
                ['Yii::createObject() configuration array must specify "class" or "__class".', 67],
                ['Yii::createObject() configuration option keys must be strings.', 83],
                ['Yii::createObject() configuration option keys must be strings.', 94],
                ['Yii::createObject() configuration option keys must be strings.', 94],
                ['Yii::createObject() configuration array must specify "class" or "__class".', 94],
                ['Yii::createObject() configuration array must specify "class" or "__class".', 101],
                ['Yii::createObject() configuration option keys must be strings.', 108],
                ['Yii::createObject() configuration option keys must be strings.', 108],
                ['Yii::createObject() configuration option keys must be strings.', 108],
                ['Yii::createObject() configuration array must specify "class" or "__class".', 108],
                [sprintf('Unknown option "unknownOption" for object %s.', PlainObject::class), 115],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return YiiCreateObjectValidationRule::class;
    }
}
