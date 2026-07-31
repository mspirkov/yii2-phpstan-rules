<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation;

final class NotActiveRecord
{
    /**
     * @param array<string, mixed> $condition
     */
    public static function findOne(array $condition): ?self
    {
        return null;
    }
}
