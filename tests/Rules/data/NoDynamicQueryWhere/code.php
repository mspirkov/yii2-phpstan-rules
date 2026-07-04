<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoDynamicQueryWhere;

use yii\db\ActiveRecord;
use yii\db\Query;

$columnValue = 'active';
$query = new Query();

$query->where("status = $columnValue");

$query->where("status = {$columnValue}");

$query->where('status = ' . $columnValue);

$query->where(['status' => $columnValue]);

$query->where('status = :status', [':status' => $columnValue]);

$query->where('status = ' . 'active');

$query->andWhere("status = $columnValue");

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

$activeQuery->where(['status' => $columnValue]);
