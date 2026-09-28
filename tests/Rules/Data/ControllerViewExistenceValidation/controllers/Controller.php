<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\controllers;

use yii\web\Controller as WebController;

final class Controller extends WebController
{
    public function actionIndex(): void
    {
        $this->render('missing');
    }
}
