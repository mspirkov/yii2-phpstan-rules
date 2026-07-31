<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation;

use yii\base\BaseObject;

final class VariadicObject extends BaseObject
{
    /** @var list<int> */
    private $rest;

    public function __construct(int ...$rest)
    {
        $this->rest = $rest;
    }
}
