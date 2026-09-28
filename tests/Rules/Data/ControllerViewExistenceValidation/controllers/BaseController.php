<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\controllers;

use yii\web\Controller;

abstract class BaseController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('missing');
    }
}
