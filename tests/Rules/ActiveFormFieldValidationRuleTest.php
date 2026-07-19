<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ActiveFormFieldValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveFormFieldValidation\ContactModel;
use yii\base\Model;

/**
 * @extends AbstractTestCase<ActiveFormFieldValidationRule>
 */
final class ActiveFormFieldValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Attribute "nickname" is not readable and writable or does not exist on model %s.', ContactModel::class), 24],
                [sprintf('Attribute "fullName" is not readable and writable or does not exist on model %s.', ContactModel::class), 29],
                [sprintf('Attribute "status" is not readable and writable or does not exist on model %s.', ContactModel::class), 39],
                [sprintf('Attribute "secret" is not readable and writable or does not exist on model %s.', ContactModel::class), 44],
                [sprintf('Attribute "nickname" is not readable and writable or does not exist on model %s.', Model::class), 80],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ActiveFormFieldValidationRule::class;
    }
}
