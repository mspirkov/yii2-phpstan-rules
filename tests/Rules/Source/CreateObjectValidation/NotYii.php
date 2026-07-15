<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\CreateObjectValidation;

final class NotYii
{
    /**
     * @param array<string, mixed> $params
     */
    public static function createObject(string $type, array $params = []): object
    {
        return new $type(...$params);
    }
}
