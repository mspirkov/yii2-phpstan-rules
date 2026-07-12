<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\WidgetPropertiesValidation;

final class NotAWidget
{
    /**
     * @param array<string, mixed> $config
     */
    public static function begin(array $config = []): self
    {
        return new self();
    }
}
