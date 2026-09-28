<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\controllers;

use yii\web\Controller;

final class TwigController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('index');
        $this->render('legacy');
        $this->render('missing');
    }
}
