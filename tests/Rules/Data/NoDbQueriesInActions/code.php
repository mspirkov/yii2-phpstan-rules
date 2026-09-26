<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoDbQueriesInApplicationClasses;

use yii\base\Action;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\mongodb\ActiveRecord as MongoActiveRecord;
use yii\redis\ActiveRecord as RedisActiveRecord;

final class LoadUserAction extends Action
{
    public function run(): void
    {
        $count = ActiveRecord::find()->count();

        \Yii::$app->get('db');
    }
}

final class LoadMongoUserAction extends Action
{
    public function run(): void
    {
        $mongoCount = MongoActiveRecord::find()->count();
    }
}

final class LoadRedisUserAction extends Action
{
    public function run(): void
    {
        $redisCount = RedisActiveRecord::find()->count();
    }
}

final class UserService
{
    public function load(): void
    {
        $user = ActiveRecord::findOne(1);
        $rows = (new Query())->all();
        $db = \Yii::$app->db;
    }
}
