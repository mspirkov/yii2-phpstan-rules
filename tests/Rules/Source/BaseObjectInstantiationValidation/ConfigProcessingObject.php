<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation;

use yii\base\BaseObject;

final class ConfigProcessingObject extends BaseObject
{
    public $name;

    private $extraOption;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['extraOption'])) {
            $this->extraOption = $config['extraOption'];
            unset($config['extraOption']);
        }

        parent::__construct($config);
    }
}
