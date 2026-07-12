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
        $modelClass = ContactModel::class;

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "nickname" for model %s.', $modelClass), 23],
                [sprintf('Unknown attribute "nickname" for model %s.', Model::class), 59],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ActiveFormFieldValidationRule::class;
    }
}
