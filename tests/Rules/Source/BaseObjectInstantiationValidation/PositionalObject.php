<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation;

use yii\base\BaseObject;

final class PositionalObject extends BaseObject
{
    public $status;

    private $id;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct($id, array $config = [])
    {
        $this->id = $id;
        parent::__construct($config);
    }
}
