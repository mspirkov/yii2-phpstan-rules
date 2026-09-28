<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerBehaviorActionsValidation;

use yii\base\Controller;

class TraitController extends Controller
{
    use ActionsTrait;
}
