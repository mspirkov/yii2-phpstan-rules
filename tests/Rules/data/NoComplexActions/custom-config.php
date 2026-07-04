<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoComplexActions;

use yii\web\Controller;

final class CustomComplexityController extends Controller
{
    public function actionIndex(bool $first, bool $second, bool $third, bool $fourth, array $items): void
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
}
