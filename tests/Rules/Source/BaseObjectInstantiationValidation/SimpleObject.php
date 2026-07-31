<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation;

use yii\base\BaseObject;

final class SimpleObject extends BaseObject
{
    public $name;

    public int $age = 0;
}
