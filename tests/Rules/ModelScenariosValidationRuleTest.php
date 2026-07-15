<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ModelScenariosValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ModelScenariosValidation\InvalidModel;

/**
 * @extends AbstractTestCase<ModelScenariosValidationRule>
 */
final class ModelScenariosValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Model scenario name cannot be empty.', 38],
                [sprintf('Unknown attribute "nickname" for model %s.', InvalidModel::class), 39],
                [sprintf('Unknown attribute "missingRole" for model %s.', InvalidModel::class), 40],
                ['Model scenario attribute name cannot be empty.', 41],
                ['Model scenario attributes must be strings.', 42],
                ['Model scenario attributes must be an array of strings.', 43],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ModelScenariosValidationRule::class;
    }
}
