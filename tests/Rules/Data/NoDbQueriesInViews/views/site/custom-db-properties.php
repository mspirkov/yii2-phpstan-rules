<?php

$reportingDb = \Yii::$app->reportingDb;

$sameConnection = \Yii::$app->get('reportingDb');

$getterConnection = \Yii::$app->getReportingDb();

$defaultDb = \Yii::$app->db;

$defaultConnection = \Yii::$app->getDb();

$mongodb = \Yii::$app->mongodb;

$mongodbConnection = \Yii::$app->getMongodb();

$redis = \Yii::$app->redis;

$redisConnection = \Yii::$app->getRedis();
