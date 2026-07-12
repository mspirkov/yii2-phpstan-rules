<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveFormFieldValidation;

final class NotActiveForm
{
    public function field(object $model, string $attribute): string
    {
        return $attribute;
    }
}
