<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\BaseObjectInstantiationValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\ConfigProcessingObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\PositionalObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\SimpleObject;

/**
 * @extends AbstractTestCase<BaseObjectInstantiationValidationRule>
 */
final class BaseObjectInstantiationValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown option "namee" for object %s.', SimpleObject::class), 36],
                [sprintf('Object option "age" for %s must be int, string given.', SimpleObject::class), 37],
                ['Object configuration option keys must be strings.', 38],
                [sprintf('Unknown option "class" for object %s.', SimpleObject::class), 39],
                [sprintf('Unknown option "statuss" for object %s.', PositionalObject::class), 40],
                [sprintf('Unknown option "namee" for object %s.', ConfigProcessingObject::class), 43],
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
        return BaseObjectInstantiationValidationRule::class;
    }
}
