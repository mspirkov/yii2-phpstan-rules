<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\Themed\Special;

use yii\web\Controller;

final class ReportController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('index');
        $this->render('missing');
    }
}
