<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ActiveFormFieldValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveFormFieldValidation\ContactModel;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveFormFieldValidation\NotActiveForm;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveFormFieldValidation\OtherModel;
use yii\base\Model;
use yii\widgets\ActiveForm;

function withDeclaredProperty(ActiveForm $form, ContactModel $model): void
{
    $form->field($model, 'name');
}

function withPhpDocProperty(ActiveForm $form, ContactModel $model): void
{
    $form->field($model, 'email');
}

function withUnknownProperty(ActiveForm $form, ContactModel $model): void
{
    $form->field($model, 'nickname');
}

function withReadOnlyProperty(ActiveForm $form, ContactModel $model): void
{
    $form->field($model, 'fullName');
}

function withGetterAndSetterProperty(ActiveForm $form, ContactModel $model): void
{
    $form->field($model, 'phone');
}

function withGetterOnlyProperty(ActiveForm $form, ContactModel $model): void
{
    $form->field($model, 'status');
}

function withSetterOnlyProperty(ActiveForm $form, ContactModel $model): void
{
    $form->field($model, 'secret');
}

function withDynamicAttribute(ActiveForm $form, ContactModel $model, string $attribute): void
{
    $form->field($model, $attribute);
}

function withTabularAttribute(ActiveForm $form, ContactModel $model, int $i): void
{
    $form->field($model, "[$i]name");
}

function withMissingArgument(ActiveForm $form, ContactModel $model): void
{
    $form->field($model);
}

function withDynamicMethodName(ActiveForm $form, ContactModel $model): void
{
    $method = 'field';
    $form->$method($model, 'nickname');
}

function withDifferentMethod(ActiveForm $form, ContactModel $model): void
{
    $form->errorSummary([$model]);
}

function withDifferentReceiver(NotActiveForm $form, ContactModel $model): void
{
    $form->field($model, 'nickname');
}

function withBaseModelType(ActiveForm $form, Model $model): void
{
    $form->field($model, 'nickname');
}

/**
 * @param ContactModel|OtherModel $model
 */
function withAmbiguousModelType(ActiveForm $form, $model): void
{
    $form->field($model, 'nickname');
}
