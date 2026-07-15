<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\CreateObjectValidation;

use yii\base\Component;

final class CreatableComponent extends Component
{
    public $label;

    public int $limit = 10;
}
