<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\Plain;

use yii\web\Controller;

final class PageController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('index');
        $this->render('missing');
    }
}
