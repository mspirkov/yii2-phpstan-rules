<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation;

use yii\base\BaseObject;

final class NoConfigObject extends BaseObject
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}
