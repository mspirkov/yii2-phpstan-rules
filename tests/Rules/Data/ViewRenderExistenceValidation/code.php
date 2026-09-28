<?php

/** @var \yii\base\View $view */
/** @var \yii\web\View $webView */
/** @var \yii\base\Component $component */
/** @var string $dynamic */
/** @var array<string> $args */

$view->render('@app/views/layouts/main');
$view->render('//layouts/main');
$webView->render('@app/views/layouts/main');

$view->render('@app/views/layouts/missing');
$view->render('//layouts/missing');
$webView->render('@app/views/layouts/missing');
$view->render('@unknown/missing');

// Skipped
$view->render('_relative');
$view->render('/site/missing');
$view->render($dynamic);
$view->render();
$view->render(...$args);
$view->renderFile('@app/views/layouts/missing');
$component->render('@app/views/layouts/missing');
