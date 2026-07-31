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
        $contactModelClass = ContactModel::class;

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "nmae" for model %s in activeLabel() call.', $contactModelClass), 26],
                [sprintf('Unknown attribute "nickname" for model %s in activeHint() call.', $contactModelClass), 27],
                [sprintf('Unknown attribute "nmae" for model %s in activeInput() call.', $contactModelClass), 28],
                [sprintf('Unknown attribute "nickname" for model %s in activeTextInput() call.', $contactModelClass), 29],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return HtmlActiveAttributeValidationRule::class;
    }
}
