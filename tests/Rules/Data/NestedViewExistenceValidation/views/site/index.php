<?php

/** @var \yii\web\View $this */
/** @var \yii\web\View $webView */
/** @var \yii\base\Component $component */
/** @var string $dynamic */
/** @var array<string> $args */
/** @var \yii\base\ViewContextInterface $context */

echo $this->render('_form');
echo $this->render('_form.php');
echo $this->render('sub/item');
echo $this->render('/layouts/main');
echo $this->render('//layouts/main');
echo $this->render('@app/views/layouts/main');
echo $webView->render('_form');

echo $this->render('_missing');
echo $this->render('_missing.php');
echo $this->render('sub/missing');
echo $this->render('/layouts/missing');
echo $this->render('//layouts/missing');
echo $this->render('@app/views/layouts/missing');
echo $webView->render('_missing');
echo $this->render('@unknown/missing');

$view = '_missing';
echo $this->render($view);

// Skipped
echo $this->render('_missing', [], $context);
echo $this->render($dynamic);
echo $this->render();
echo $this->render(...$args);
echo $this->renderFile('_missing');
echo $component->render('_missing');
