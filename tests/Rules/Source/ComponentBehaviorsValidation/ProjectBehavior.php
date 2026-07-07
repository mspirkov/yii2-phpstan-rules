<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ComponentBehaviorsValidation;

use yii\base\Behavior;

final class ProjectBehavior extends Behavior
{
    public string $name = '';

    public bool $enabled = true;

    /** @var mixed */
    public $mixedValue;

    private int $threshold = 0;

    public static string $staticOption = '';

    public function setThreshold(int $threshold): void
    {
        $this->threshold = $threshold;
    }
}
