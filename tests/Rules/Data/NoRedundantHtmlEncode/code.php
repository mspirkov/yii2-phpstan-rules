<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\NoRedundantHtmlEncode;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\NoRedundantHtmlEncode\CustomHtml;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\NoRedundantHtmlEncode\NotHtml;
use yii\helpers\Html;

/**
 * @param numeric-string $numericString
 */
function withNumericStringParam(string $numericString): string
{
    return Html::encode($numericString);
}

function withNarrowedNumericString(string $value): string
{
    if (is_numeric($value)) {
        return Html::encode($value);
    }

    return Html::encode($value);
}

function withInt(int $count): string
{
    return Html::encode($count);
}

function withIntCastToString(int $id): string
{
    return Html::encode((string) $id);
}

/**
 * @param numeric-string $numericString
 */
function withCustomHtmlSubclass(string $numericString): string
{
    return CustomHtml::encode($numericString);
}

/**
 * @param numeric-string $numericString
 */
function withUnrelatedEncodeMethod(string $numericString): string
{
    return NotHtml::encode($numericString);
}

function withDifferentMethod(): string
{
    return Html::tag('div', 'content');
}

function withDynamicClassName(string $numericString): string
{
    $class = Html::class;

    return $class::encode($numericString);
}

function withoutArguments(): string
{
    return Html::encode();
}
