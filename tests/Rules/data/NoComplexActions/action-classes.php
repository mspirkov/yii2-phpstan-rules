<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoComplexActions;

use yii\base\Action;

final class ComplexAction extends Action
{
    public function run(bool $first, bool $second, bool $third, bool $fourth, array $items): void
    {
        if ($first) {
        }

        if ($second) {
        }

        if ($third) {
        }

        if ($fourth) {
        }

        foreach ($items as $item) {
        }
    }

    public function helper(array $items): void
    {
        foreach ($items as $item) {
        }
    }
}

final class ThinAction extends Action
{
    public function run(bool $first): void
    {
        if ($first) {
        }
    }
}

final class NotAction
{
    public function run(array $items): void
    {
        foreach ($items as $item) {
        }
    }
}

trait ActionTrait
{
    public function run(array $items): void
    {
        foreach ($items as $item) {
        }
    }
}
