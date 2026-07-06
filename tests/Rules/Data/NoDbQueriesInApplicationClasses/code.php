<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoDbQueriesInApplicationClasses;

use yii\base\Action;
use yii\db\ActiveRecord;
use yii\db\Query;
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
}

final class LoadUserAction extends Action
{
    public function run(): void
    {
        $count = ActiveRecord::find()->count();

        \Yii::$app->get('db');
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
