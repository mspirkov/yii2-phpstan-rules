<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelAttributeLabelsValidation;

use yii\base\Model;

/**
 * @property string $email
 */
final class ValidModel extends Model
{
    public $login;
    public $password;

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'login' => 'Login',
            'password' => 'Password',
            'email' => 'E-mail',
        ]);
    }
}

final class InvalidModel extends Model
{
    public $login;
    public $password;

    public function attributeLabels(): array
    {
        return [
            'login' => 'Login',
            'nickname' => 'Nickname',
            'password' => 42,
            'oops',
            '' => 'Empty name',
            'unknownAndBadType' => 123,
        ];
    }
}

final class DynamicLabelModel extends Model
{
    public $login;

    public function attributeLabels(): array
    {
        $label = $this->buildLabel();

        return [
            'login' => $label,
        ];
    }

    private function buildLabel(): string
    {
        return 'Login';
    }
}

final class SpreadLabelModel extends Model
{
    public $login;

    public function attributeLabels(): array
    {
        $extra = ['login' => 'Login'];

        return [
            ...$extra,
        ];
    }
}

final class NotInterestingMethod
{
    public function attributeLabels(): array
    {
        return [
            'unrelated' => 123,
        ];
    }
}

final class MagicAttributeModel extends Model
{
    public $login;

    public function attributeLabels(): array
    {
        return [
            'login' => 'Login',
            'fullName' => 'Full name',
            'secret' => 'Secret',
        ];
    }

    public function getFullName(): string
    {
        return 'Full Name';
    }

    public function setSecret(string $value): void
    {
    }
}

final class AttributeNameShapeModel extends Model
{
    public $login;

    public function attributeLabels(): array
    {
        return [
            'user.name' => 'User name',
            'COALESCE(map_id, 0)' => 'Map',
            ' login ' => 'Login',
            ' nickname ' => 'Nickname',
        ];
    }
}
