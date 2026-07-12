<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\WidgetPropertiesValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\WidgetPropertiesValidation\MyWidget;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\WidgetPropertiesValidation\NotAWidget;

function withKnownProperties(): void
{
    MyWidget::begin([
        'label' => 'Hello',
        'limit' => 5,
    ]);
}

function withUnknownProperty(): void
{
    MyWidget::begin([
        'unknownOption' => 'x',
    ]);
}

function withWrongValueType(): void
{
    MyWidget::begin([
        'limit' => 'not-an-int',
    ]);
}

function withEventAndBehaviorKeys(): void
{
    MyWidget::begin([
        'label' => 'Hello',
        'on afterRun' => 'someHandler',
        'as myBehavior' => 'app\behaviors\MyBehavior',
    ]);
}

function withPositionalArgument(): void
{
    MyWidget::begin([
        'label' => 'Hello',
        'extra-positional-value',
    ]);
}

function withWidgetMethod(): void
{
    MyWidget::widget([
        'unknownOption' => 'x',
    ]);
}

function withEndMethod(): void
{
    MyWidget::end();
}

function withNoArguments(): void
{
    MyWidget::begin();
}

function withNonArrayArgument(): void
{
    $config = ['unknownOption' => 'x'];

    MyWidget::begin($config);
}

function withNonWidgetClass(): void
{
    NotAWidget::begin(['unknownOption' => 'x']);
}

function withDynamicClassName(): void
{
    $class = MyWidget::class;

    $class::begin(['unknownOption' => 'x']);
}
