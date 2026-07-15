<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\CreateObjectValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\CreateObjectValidation\CreatableComponent;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\CreateObjectValidation\NotYii;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\CreateObjectValidation\PlainObject;

function withKnownStringClass(): void
{
    \Yii::createObject(CreatableComponent::class);
}

function withKnownConfigArray(): void
{
    \Yii::createObject([
        'class' => CreatableComponent::class,
        'label' => 'Hello',
        'limit' => 5,
    ]);
}

function withUnknownConfigOption(): void
{
    \Yii::createObject([
        'class' => CreatableComponent::class,
        'unknownOption' => 'x',
    ]);
}

function withWrongConfigValueType(): void
{
    \Yii::createObject([
        'class' => CreatableComponent::class,
        'limit' => 'not-an-int',
    ]);
}

function withEventAndBehaviorKeys(): void
{
    \Yii::createObject([
        'class' => CreatableComponent::class,
        'on afterRun' => 'someHandler',
        'as myBehavior' => 'app\behaviors\MyBehavior',
    ]);
}

function withMissingClassKey(): void
{
    \Yii::createObject([
        'label' => 'Hello',
    ]);
}

function withNullClass(): void
{
    \Yii::createObject([
        'class' => null,
    ]);
}

function withInvalidOptionKey(): void
{
    \Yii::createObject([
        'class' => CreatableComponent::class,
        'extra-positional-value',
    ]);
}

function withCallableArray(): void
{
    \Yii::createObject([NotYii::class, 'createObject']);
}

function withMismatchedCallableShape(): void
{
    \Yii::createObject([CreatableComponent::class, 5]);
}

function withUnpackedArray(): void
{
    $parts = [CreatableComponent::class, 'someMethod'];

    \Yii::createObject([...$parts]);
}

function withTooManyItemsAndUnpack(): void
{
    $rest = [];

    \Yii::createObject(['a', 'b', 'c', ...$rest]);
}

function withPlainObjectConfig(): void
{
    \Yii::createObject([
        'class' => PlainObject::class,
        'unknownOption' => 'x',
    ]);
}

function withUnknownConfigClass(): void
{
    \Yii::createObject([
        'class' => 'App\Unknown',
    ]);
}

function withClosureType(): void
{
    \Yii::createObject(static function (): CreatableComponent {
        return new CreatableComponent();
    });
}

function withDynamicArrayArgument(): void
{
    $config = [
        'class' => CreatableComponent::class,
        'unknownOption' => 'x',
    ];

    \Yii::createObject($config);
}

function withParamsArgument(): void
{
    \Yii::createObject(CreatableComponent::class, [1, 2]);
}

function withNoArguments(): void
{
    \Yii::createObject();
}

function withNonYiiClass(): void
{
    NotYii::createObject(CreatableComponent::class);
}

function withDynamicClassName(): void
{
    $class = \Yii::class;

    $class::createObject(CreatableComponent::class);
}

function withOtherStaticMethod(): void
{
    \Yii::getAlias('@app');
}
