<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\custom;

use yii\web\Controller;

final class LegacyController extends Controller
{
    public function actionIndex(): void
    {
        $this->render('missing');
    }
}
