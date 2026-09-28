<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\controllers;

use yii\web\Controller;

final class OverriddenViewPathController extends Controller
{
    public function getViewPath(): string
    {
        return __DIR__;
    }

    public function actionIndex(): void
    {
        $this->render('missing');
    }
}
