<?php

$reportingDb = \Yii::$app->reportingDb;

$sameConnection = \Yii::$app->get('reportingDb');

$getterConnection = \Yii::$app->getReportingDb();

$defaultDb = \Yii::$app->db;

$defaultConnection = \Yii::$app->getDb();
