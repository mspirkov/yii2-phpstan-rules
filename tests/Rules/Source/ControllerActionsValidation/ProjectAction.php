<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerActionsValidation;

use yii\base\Action;

final class ProjectAction extends Action
{
    public string $view = '';

    public bool $enabled = true;

    /** @var mixed */
    public $mixedValue;

    private int $threshold = 0;

    public function setThreshold(int $threshold): void
    {
        $this->threshold = $threshold;
    }
}
