<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\controllers;

use yii\web\Controller;

final class LandingPageHandler extends Controller
{
    public function actionIndex(): void
    {
        $this->render('missing');
    }
}
