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
        $invalidModelClass = 'MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelAttributeLabelsValidation\InvalidModel';

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "nickname" for model %s.', $invalidModelClass), 34],
                ['Model attribute label contains an empty attribute name.', 37],
                [sprintf('Unknown attribute "unknownAndBadType" for model %s.', $invalidModelClass), 38],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ModelAttributeLabelsValidationRule::class;
    }
}
