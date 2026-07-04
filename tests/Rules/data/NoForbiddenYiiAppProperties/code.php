<?php

$id = Yii::$app->id;
$name = Yii::$app->name;
$charset = Yii::$app->charset;
$language = Yii::$app->language;
$timeZone = Yii::$app->timeZone;

$request = Yii::$app->request;

$propertyName = 'request';
$dynamic = Yii::$app->{$propertyName};

$nullsafe = Yii::$app?->request;

$byFqcn = \Yii::$app->db;

$headers = $request->headers;
