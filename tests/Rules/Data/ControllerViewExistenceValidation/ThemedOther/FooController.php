<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\ThemedOther;

use yii\web\Controller;

final class FooController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('index');
        $this->render('missing');
    }
}
