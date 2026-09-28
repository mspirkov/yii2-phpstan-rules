<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\controllers;

use yii\web\Controller;
use yii\web\View;

final class SkippedController extends Controller
{
    /**
     * @param \yii\web\Controller|\yii\console\Controller $unionController
     */
    public function actionSkipped(
        string $dynamic,
        array $args,
        View $view,
        NotController $notController,
        Controller $baseController,
        $unionController
    ): void {
        $this->render($dynamic);
        $this->render();
        $this->render(...$args);
        $this->renderContent('missing');
        $this->renderFile('missing');
        $this->{$dynamic}('missing');
        $view->render('_missing');
        $notController->render('missing');
        $baseController->render('missing');
        $unionController->render('missing');
    }
}

final class NotController
{
    public function render(string $view): void
    {
    }

    public function helper(): void
    {
        $this->render('missing');
    }
}
