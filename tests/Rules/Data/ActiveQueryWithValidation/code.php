<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ActiveQueryWithValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation\Customer;
use yii\db\ActiveQuery;

function validVariadicArgs(): void
{
    Customer::find()->with('orders', 'address')->all();
}

function validArrayArgs(): void
{
    Customer::find()->with([
        'orders' => static function (ActiveQuery $query): void {
            $query->andWhere(['status' => 1]);
        },
        'address',
    ])->all();
}

function validNestedViaTypedGetter(): void
{
    Customer::find()->with('orders.items')->all();
}

function validNestedViaPropertyRead(): void
{
    Customer::find()->with('address.country')->all();
}

function validJoinWithAlias(): void
{
    Customer::find()->joinWith('orders o')->all();
}

function validJoinWithAliasAsKeyword(): void
{
    Customer::find()->joinWith(['orders AS o'])->all();
}

function validInnerJoinWithArray(): void
{
    Customer::find()->innerJoinWith([
        'orders' => static function (ActiveQuery $query): void {
            $query->andWhere(['status' => 1]);
        },
    ])->all();
}

function invalidUnknownRelation(): void
{
    Customer::find()->with('bogus')->all();
}

function invalidNotARelation(): void
{
    Customer::find()->with('displayName')->all();
}

function invalidNestedUnknownChild(): void
{
    Customer::find()->with('orders.bogus')->all();
}

function invalidArrayKeyRelation(): void
{
    Customer::find()->with(['bogus' => static function (ActiveQuery $query): void {
    }])->all();
}

function invalidJoinWithAlias(): void
{
    Customer::find()->joinWith('bogus o')->all();
}

function invalidVariadicSecondArg(): void
{
    Customer::find()->with('orders', 'bogus')->all();
}

function invalidNestedViaPropertyReadArray(): void
{
    Customer::find()->with('tags.bogus')->all();
}

function skippedUnresolvedBaseClass(ActiveQuery $query): void
{
    $query->with('bogus')->all();
}

function skippedUnresolvedNestedRelation(): void
{
    Customer::find()->with('country.bogus')->all();
}

function skippedDynamicRelationName(string $relation): void
{
    Customer::find()->with($relation)->all();
}

function skippedDynamicMethodName(): void
{
    $method = 'with';
    Customer::find()->$method('bogus')->all();
}

function skippedZeroArgsWith(): void
{
    Customer::find()->with()->all();
}

function skippedVariadicUnpackedArgs(): void
{
    $relations = ['orders'];
    Customer::find()->with(...$relations)->all();
}

function skippedArrayUnpackedItem(): void
{
    $extra = ['country'];
    Customer::find()->with([...$extra, 'orders'])->all();
}

function skippedDynamicArrayItemName(string $relation): void
{
    Customer::find()->with([$relation])->all();
}
