<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerBehaviorActionsValidation;

use yii\base\Controller;
use yii\web\ErrorAction;

class BaseController extends Controller
{
    public function actions(): array
    {
        return array_merge(parent::actions(), [
            'inherited' => ErrorAction::class,
        ]);
    }

    public function actionFromBase(): void
    {
    }
}
