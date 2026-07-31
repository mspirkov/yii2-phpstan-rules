<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\UploadedFileInstanceValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\UploadedFileInstanceValidation\NotUploadedFile;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\UploadedFileInstanceValidation\OtherForm;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\UploadedFileInstanceValidation\UploadForm;
use yii\base\DynamicModel;
use yii\web\UploadedFile;

final class ValidFormUsage
{
    public function run(UploadForm $model): void
    {
        UploadedFile::getInstance($model, 'imageFile');
        UploadedFile::getInstance($model, 'description');
        UploadedFile::getInstances($model, 'imageFile');
    }
}

final class InvalidFormUsage
{
    public function run(UploadForm $model): void
    {
        UploadedFile::getInstance($model, 'imagefile');
        UploadedFile::getInstances($model, 'imageFiles');
    }
}

final class SkippedFormUsage
{
    public function run(UploadForm $model, string $dynamicAttribute): void
    {
        // Not a checked method.
        UploadedFile::reset();

        // Not UploadedFile at all.
        NotUploadedFile::getInstance($model, 'imagefile');

        // Missing the attribute argument.
        UploadedFile::getInstance($model);

        // Attribute name isn't a resolvable single string constant.
        UploadedFile::getInstance($model, $dynamicAttribute);

        // Dynamic class / dynamic method name — not a plain Name / Identifier.
        $className = UploadedFile::class;
        $className::getInstance($model, 'imagefile');
        $methodName = 'getInstance';
        UploadedFile::$methodName($model, 'imagefile');
    }

    /**
     * @param UploadForm|OtherForm $model
     */
    public function withAmbiguousModelType($model): void
    {
        UploadedFile::getInstance($model, 'nickname');
    }

    public function withDynamicModel(DynamicModel $model): void
    {
        UploadedFile::getInstance($model, 'nickname');
    }
}
