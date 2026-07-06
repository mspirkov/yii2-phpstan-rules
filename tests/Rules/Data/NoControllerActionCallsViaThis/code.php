<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoControllerActionCallsViaThis;

use yii\web\Controller;

final class SiteController extends Controller
{
    public function actionIndex(): void
    {
    }

    public function actionView(): void
    {
        $this->actionIndex();
    }

    public function helper(): void
    {
        $this->render('index');
        $this->actions();

        $method = 'actionIndex';
        $this->{$method}();

        $this->actionView();

        $controller = $this;
        $controller->actionIndex();
    }
}

final class NotController
{
    public function actionIndex(): void
    {
    }

    public function helper(): void
    {
        $this->actionIndex();
    }
}
