<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\NoRedundantHtmlEncode;

final class NotHtml
{
    public static function encode(string $content): string
    {
        return $content;
    }
}
