<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\HtmlActiveAttributeValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\HtmlActiveAttributeValidation\ContactModel;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\HtmlActiveAttributeValidation\NotHtml;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\HtmlActiveAttributeValidation\OtherModel;
use yii\base\DynamicModel;
use yii\helpers\Html;

final class ValidContactUsage
{
    public function run(ContactModel $model): void
    {
        Html::activeLabel($model, 'name');
        Html::activeHint($model, 'email');
        Html::activeInput('text', $model, 'name');
        Html::activeTextInput($model, 'email');
    }
}

final class InvalidContactUsage
{
    public function run(ContactModel $model): void
    {
        Html::activeLabel($model, 'nmae');
        Html::activeHint($model, 'nickname');
        Html::activeInput('text', $model, 'nmae');
        Html::activeTextInput($model, 'nickname');
    }
}

final class SkippedContactUsage
{
    public function run(ContactModel $model, string $dynamicAttribute): void
    {
        // Not a checked method.
        Html::encode('x');

        // Not Html/BaseHtml at all.
        NotHtml::activeLabel($model, 'nickname');

        // Missing the attribute argument.
        Html::activeLabel($model);

        // Missing even the model argument.
        Html::activeInput('text');

        // Attribute name isn't a resolvable single string constant.
        Html::activeLabel($model, $dynamicAttribute);

        // Dynamic class / dynamic method name — not a plain Name / Identifier.
        $className = Html::class;
        $className::activeLabel($model, 'nickname');
        $methodName = 'activeLabel';
        Html::$methodName($model, 'nickname');
    }

    /**
     * @param ContactModel|OtherModel $model
     */
    public function withAmbiguousModelType($model): void
    {
        Html::activeLabel($model, 'nickname');
    }

    public function withDynamicModel(DynamicModel $model): void
    {
        Html::activeLabel($model, 'nickname');
    }
}
