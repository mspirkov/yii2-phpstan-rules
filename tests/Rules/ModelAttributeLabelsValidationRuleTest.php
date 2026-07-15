<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ModelAttributeLabelsValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelAttributeLabelsValidation\AttributeNameShapeModel;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelAttributeLabelsValidation\InvalidModel;

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
                [sprintf('Unknown attribute "nickname" for model %s.', InvalidModel::class), 34],
                ['Model attribute label contains an empty attribute name.', 37],
                [sprintf('Unknown attribute "unknownAndBadType" for model %s.', InvalidModel::class), 38],
                [sprintf('Unknown attribute " login " for model %s.', AttributeNameShapeModel::class), 118],
                [sprintf('Unknown attribute " nickname " for model %s.', AttributeNameShapeModel::class), 119],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ModelAttributeLabelsValidationRule::class;
    }
}
