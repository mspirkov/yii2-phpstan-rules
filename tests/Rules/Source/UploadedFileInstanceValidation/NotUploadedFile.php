<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\UploadedFileInstanceValidation;

final class NotUploadedFile
{
    /**
     * @param \yii\base\Model $model
     * @param string $attribute
     * @return static|null
     */
    public static function getInstance($model, $attribute)
    {
        return null;
    }
}
