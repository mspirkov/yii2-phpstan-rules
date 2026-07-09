<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ModelAttributeLabelsValidationRule;

/**
 * @extends AbstractTestCase<ModelAttributeLabelsValidationRule>
 */
final class ModelAttributeLabelsValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [
                    'Unknown attribute "nickname" for model MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelAttributeLabelsValidation\InvalidModel.',
                    34,
                ],
                [
                    'Model attribute label contains an empty attribute name.',
                    37,
                ],
                [
                    'Unknown attribute "unknownAndBadType" for model MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelAttributeLabelsValidation\InvalidModel.',
                    38,
                ],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ModelAttributeLabelsValidationRule::class;
    }
}
