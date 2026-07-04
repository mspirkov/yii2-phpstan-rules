<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ModelRulesValidation;

use yii\base\Model;

final class BrokenBuiltInValidatorModel extends Model
{
    public $name;

    public function rules(): array
    {
        return [
            ['name', 'brokenBuiltIn'],
        ];
    }
}
