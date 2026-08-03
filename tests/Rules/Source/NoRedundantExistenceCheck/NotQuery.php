<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\NoRedundantExistenceCheck;

final class NotQuery
{
    public function one(): ?self
    {
        return null;
    }

    public function count(): int
    {
        return 0;
    }
}
