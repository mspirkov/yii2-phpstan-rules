<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\UploadedFileInstanceValidation;

use yii\base\Model;

final class NotUploadedFile
{
    /**
     * @param Model $model
     * @param string $attribute
     * @return static|null
     */
    public static function getInstance($model, $attribute)
    {
        return null;
    }
}
