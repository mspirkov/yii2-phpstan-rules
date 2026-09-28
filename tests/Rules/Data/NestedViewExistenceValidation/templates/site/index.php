<?php

/** @var \yii\web\View $this */

echo $this->render('_form');
echo $this->render('/site/_form');
echo $this->render('/layouts/main');

echo $this->render('_missing');
echo $this->render('/site/_missing');
echo $this->render('/layouts/missing');
