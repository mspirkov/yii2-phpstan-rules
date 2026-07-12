<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\WidgetPropertiesValidation;

use yii\base\Widget;

final class MyWidget extends Widget
{
    public $label;

    public int $limit = 10;
}
