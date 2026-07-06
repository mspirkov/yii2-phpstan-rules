<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\NoDbQueriesInViews;

final class ViewHelper
{
    public function loadUsers(): void
    {
    }

    public function passthrough(): self
    {
        return $this;
    }

    public function render(): void
    {
    }
}
