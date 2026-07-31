<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation;

final class NotActiveRecord
{
    /**
     * @param array<string, mixed> $attributes
     */
    public static function updateAll(array $attributes): int
    {
        return 0;
    }
}
