<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\controllers;

use yii\web\Controller;

final class MisplacedController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('missing');
    }
}
