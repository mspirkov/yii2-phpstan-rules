<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation;

use yii\base\BaseObject;

final class ThirdPartyObject extends BaseObject
{
    private $apiKey;

    private $timeout;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->apiKey = $config['apiKey'] ?? null;
        $this->timeout = $config['timeout'] ?? 30;
    }
}
