<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\HtmlActiveAttributeValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\HtmlActiveAttributeValidation\ContactModel;

/**
 * @extends AbstractTestCase<HtmlActiveAttributeValidationRule>
 */
final class HtmlActiveAttributeValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "nmae" for model %s in activeLabel() call.', ContactModel::class), 26],
                [sprintf('Unknown attribute "nickname" for model %s in activeHint() call.', ContactModel::class), 27],
                [sprintf('Unknown attribute "nmae" for model %s in activeInput() call.', ContactModel::class), 28],
                [sprintf('Unknown attribute "nickname" for model %s in activeTextInput() call.', ContactModel::class), 29],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return HtmlActiveAttributeValidationRule::class;
    }
}
