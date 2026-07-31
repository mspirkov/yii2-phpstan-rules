<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\QueryConditionValidation;

final class NotQuery
{
    /**
     * @param array<mixed> $condition
     */
    public function where($condition): self
    {
        return $this;
    }
}
