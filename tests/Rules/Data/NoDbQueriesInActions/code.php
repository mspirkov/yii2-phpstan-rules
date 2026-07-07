<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoDbQueriesInApplicationClasses;

use yii\base\Action;
use yii\db\ActiveRecord;
use yii\db\Query;

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
