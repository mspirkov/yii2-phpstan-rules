<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoDbQueriesInApplicationClasses;

use yii\db\ActiveRecord;
use yii\db\Query;
use yii\mongodb\ActiveRecord as MongoActiveRecord;
use yii\mongodb\Command as MongoCommand;
use yii\mongodb\Connection as MongoConnection;
use yii\mongodb\Query as MongoQuery;
use yii\mongodb\Transaction as MongoTransaction;
use yii\redis\ActiveRecord as RedisActiveRecord;
use yii\redis\Connection as RedisConnection;
use yii\web\Controller;

final class SiteController extends Controller
{
    public function actionIndex(): void
    {
        $db = \Yii::$app->db;

        $connection = \Yii::$app->getDb();

        $rows = (new Query())->from('user')->all();

        $user = ActiveRecord::findOne(1);

        ActiveRecord::find()->where(['active' => true])->all();

        $this->render('index');
    }

    public function actionSave(ActiveRecord $user): void
    {
        $user->save();
    }

    public function actionMongo(): void
    {
        $mongoRows = (new MongoQuery())->from('users')->all();

        $mongoUser = MongoActiveRecord::findOne(1);

        MongoActiveRecord::find()->where(['active' => true])->all();
    }

    public function actionRedis(): void
    {
        $redisUser = RedisActiveRecord::findOne(1);

        RedisActiveRecord::find()->where(['active' => true])->all();
    }

    public function actionSaveMongo(MongoActiveRecord $mongoUser): void
    {
        $mongoUser->save();
    }

    public function actionSaveRedis(RedisActiveRecord $redisUser): void
    {
        $redisUser->save();
    }

    public function actionMongoConnection(
        MongoConnection $connection,
        MongoCommand $command,
        MongoTransaction $transaction
    ): void {
        $connection->open();
        $command->execute();
        $transaction->commit();
    }

    public function actionRedisConnection(RedisConnection $connection): void
    {
        $connection->open();
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
