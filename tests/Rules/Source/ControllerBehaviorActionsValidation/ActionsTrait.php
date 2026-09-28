<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerBehaviorActionsValidation;

use yii\web\ErrorAction;

trait ActionsTrait
{
    public function actions(): array
    {
        return [
            'from-trait' => ErrorAction::class,
        ];
    }
}
