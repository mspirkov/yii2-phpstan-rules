<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\UploadedFileInstanceValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\UploadedFileInstanceValidation\UploadForm;

/**
 * @extends AbstractTestCase<UploadedFileInstanceValidationRule>
 */
final class UploadedFileInstanceValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $uploadFormClass = UploadForm::class;

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "imagefile" for model %s in getInstance() call.', $uploadFormClass), 25],
                [sprintf('Unknown attribute "imageFiles" for model %s in getInstances() call.', $uploadFormClass), 26],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return UploadedFileInstanceValidationRule::class;
    }
}
