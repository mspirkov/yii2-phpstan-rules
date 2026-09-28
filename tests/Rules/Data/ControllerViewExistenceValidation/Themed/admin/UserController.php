<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\Themed\admin;

use yii\web\Controller;

final class UserController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('index');
        $this->render('missing');
    }
}
