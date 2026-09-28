<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\NoViewsModule\controllers;

use yii\web\Controller;

final class EmptyController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('index');
    }
}
