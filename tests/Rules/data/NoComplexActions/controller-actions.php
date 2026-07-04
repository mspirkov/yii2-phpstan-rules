<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoComplexActions;

use yii\web\Controller;

final class ComplexController extends Controller
{
    public function actionAllowed(bool $first, bool $second, bool $third): void
    {
        if ($first) {
        }

        if ($second) {
        } elseif ($third) {
        }
    }

    public function actionTooManyIfs(bool $first, bool $second, bool $third, bool $fourth, bool $fifth): void
    {
        if ($first) {
        }

        if ($second) {
        }

        if ($third) {
        }

        if ($fourth) {
        }

        if ($fifth) {
        }
    }

    public function actionBusinessLogic(array $items, bool $condition, string $status): void
    {
        foreach ($items as $item) {
        }

        for ($i = 0; $i < 10; $i++) {
        }

        while ($condition) {
        }

        do {
        } while ($condition);

        switch ($status) {
            case 'active':
                break;
        }

        $value = match ($status) {
            'active' => 1,
            default => 0,
        };

        $label = $condition ? 'yes' : 'no';

        try {
            $this->runRiskyOperation();
        } catch (\Throwable $exception) {
        }
    }

    public function actions(array $items): array
    {
        foreach ($items as $item) {
        }

        return [];
    }
}

final class NotController
{
    public function actionIndex(array $items): void
    {
        foreach ($items as $item) {
        }
    }
}

trait ControllerActionTrait
{
    public function actionFromTrait(array $items): void
    {
        foreach ($items as $item) {
        }
    }
}
