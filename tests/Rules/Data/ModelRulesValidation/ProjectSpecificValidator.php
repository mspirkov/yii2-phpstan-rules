<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ModelRulesValidation;

use yii\validators\Validator;

final class ProjectSpecificValidator extends Validator
{
    /**
     * @var int|null
     */
    public $max;
}
