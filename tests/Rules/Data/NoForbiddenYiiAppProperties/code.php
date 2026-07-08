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

$app = Yii::$app;
$idViaVariable = $app->id;
$requestViaVariable = $app->request;

$appAlias = $app;
$dbViaAlias = $appAlias->db;

$application = new \yii\web\Application([]);
$applicationId = $application->id;
$applicationRequest = $application->request;
$applicationDynamic = $application->{$propertyName};
