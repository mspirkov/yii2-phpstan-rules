<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation;

use yii\base\BaseObject;

final class NoParamsObject extends BaseObject
{
    public function __construct()
    {
        parent::__construct([]);
    }
}
