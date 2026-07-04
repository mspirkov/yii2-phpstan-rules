<?php

$id = Yii::$app->id;
$request = Yii::$app->request;

Yii::$app->name = 'app';

Yii::$app->language .= '-RU';

Yii::$app->params['feature']['enabled'] = true;

Yii::$app->counter++;
--Yii::$app->counter;

$db = null;
Yii::$app->db =& $db;

unset(Yii::$app->cache);

$propertyName = 'name';
Yii::$app->{$propertyName} = 'value';

Yii::$app->setComponents([]);

\Yii::$app->setComponents([]);

Yii::$app->getComponents();
$component = Yii::$app->get('db');
$request->headers = [];
(new class {
    public function setComponents(array $components): void
    {
    }
})->setComponents([]);
$methodName = 'getComponents';
Yii::$app->{$methodName}();
