<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoDynamicQueryWhere;

use yii\db\ActiveRecord;
use yii\db\Query;
use yii\mongodb\Query as MongoQuery;
use yii\redis\ActiveRecord as RedisActiveRecord;

$columnValue = 'active';
$ids = '1,2,3';
$query = new Query();

$query->limit(10);

$query->where("status = $columnValue");

$query->where("status = {$columnValue}");

$query->where('status = ' . $columnValue);

$query->where(['status' => $columnValue]);

$query->where('status = :status', [':status' => $columnValue]);

$query->where('status = ' . 'active');

$query->andWhere("status = $columnValue");

$query->orWhere("status = $columnValue");

$query->andWhere("status IN ($ids)");

$query->orWhere('status IN (' . $ids . ')');

$query->andWhere(['in', 'status', [1, 2, 3]]);

$query->orWhere(['status' => $columnValue]);

$query->where();

$methodName = 'where';
$query->{$methodName}("status = $columnValue");

(new class {
    public function where(string $condition): void
    {
    }
})->where("status = $columnValue");

$activeQuery = ActiveRecord::find();

$activeQuery->where("status = $columnValue");

$activeQuery->andWhere("status = $columnValue");

$activeQuery->where(['status' => $columnValue]);

$mongoQuery = new MongoQuery();

$mongoQuery->where("status = $columnValue");

$mongoQuery->andWhere("status = $columnValue");

$mongoQuery->where(['status' => $columnValue]);

$redisActiveQuery = RedisActiveRecord::find();

$redisActiveQuery->where("status = $columnValue");

$redisActiveQuery->orWhere("status = $columnValue");

$redisActiveQuery->where(['status' => $columnValue]);
