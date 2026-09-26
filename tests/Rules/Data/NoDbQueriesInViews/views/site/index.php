<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoDbQueriesInViews;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\NoDbQueriesInViews\ViewHelper;
use yii\db\ActiveRecord;
use yii\db\Command;
use yii\db\Connection;
use yii\db\Query;
use yii\db\Transaction;
use yii\mongodb\ActiveRecord as MongoActiveRecord;
use yii\mongodb\Query as MongoQuery;
use yii\redis\ActiveRecord as RedisActiveRecord;

$title = 'Users';
$db = \Yii::$app->db;

$connection = \Yii::$app->getDb();

$sameConnection = \Yii::$app->get('db');

$rows = (new Query())->from('user')->all();

$user = ActiveRecord::findOne(1);
$activeQuery = ActiveRecord::find();

$activeQuery->count();

(new ViewHelper())->loadUsers();

function inspectDatabaseAccess(
    Connection $connection,
    Command $command,
    Transaction $transaction,
    array $connections,
    string $componentName,
    string $methodName,
    string $appProperty,
    string $className
): void {
    $connection->open();
    $command->queryAll();
    $transaction->commit();

    \Yii::$app->db->createCommand('SELECT 1');
    \Yii::$app->getDb()->createCommand('SELECT 1');

    \Yii::$app->{$componentName};
    \Yii::$app->cache;
    \Yii::$app->get('cache');
    \Yii::$app->get();
    \Yii::$app->getCache();

    $className::findOne(1);
    ActiveRecord::{$methodName}(1);
    ActiveRecord::className();

    $className::$app->db;
    \Yii::${$appProperty}->db;
    \Yii::$container->db;

    $query = new Query();
    $connections[0]->createCommand('SELECT 1');
    (new \ArrayObject())->count();
    $query->{$methodName}();
}

(new ViewHelper())->passthrough()->render();

$mongoRows = (new MongoQuery())->from('users')->all();

$mongoUser = MongoActiveRecord::findOne(1);
$mongoActiveQuery = MongoActiveRecord::find();

$mongoActiveQuery->count();

$redisUser = RedisActiveRecord::findOne(1);
$redisActiveQuery = RedisActiveRecord::find();

$redisActiveQuery->count();
