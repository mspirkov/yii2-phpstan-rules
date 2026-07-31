<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\HtmlActiveAttributeValidation;

use yii\base\Model;

final class NotHtml
{
    /**
     * @param Model $model
     * @param string $attribute
     * @param array<string, mixed> $options
     */
    public static function activeLabel($model, $attribute, $options = []): string
    {
        return $attribute;
    }
}
