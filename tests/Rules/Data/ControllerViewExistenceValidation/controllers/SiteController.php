<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerViewExistenceValidation\controllers;

use yii\web\Controller;

final class SiteController extends Controller
{
    private const EXISTING = 'index';

    public function actionValid(): void
    {
        $this->render('index');
        $this->render('index.php');
        $this->render('/site/index');
        $this->render(self::EXISTING);
        $this->renderPartial('_form');
        $this->renderAjax('_form');
        $this->render('sub/nested');
        $this->render('@app/views/shared/banner');
        $this->render('@shared/banner');
        $this->render('//layouts/main');
    }

    public function actionInvalid(SiteController $controller): void
    {
        $this->render('missing');
        $this->renderPartial('_missing');
        $this->renderAjax('missing');
        $this->render('/site/missing');
        $this->render('missing.php');
        $this->render('sub/missing');
        $this->render('@app/views/shared/missing');
        $this->render('@shared/missing');
        $this->render('//layouts/missing');
        $this->render('@unknown/missing');
        $controller->render('missing');

        $view = 'missing';
        $this->render($view);
    }
}
