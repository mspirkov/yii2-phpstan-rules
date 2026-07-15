<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ModelScenariosValidation;

use yii\base\Model;

final class ValidModel extends Model
{
    public $login;
    public $password;

    public function scenarios(): array
    {
        return array_merge(parent::scenarios(), [
            'login' => ['login', 'password'],
            'register' => ['login', 'password', '!role'],
        ]);
    }

    public function getRole(): string
    {
        return 'user';
    }

    public function setRole(string $value): void
    {
    }
}

final class InvalidModel extends Model
{
    public $login;

    public function scenarios(): array
    {
        return [
            0 => ['login'],
            '' => ['login'],
            'login' => ['login', 'nickname'],
            'register' => ['login', '!missingRole'],
            'emptyAttribute' => [''],
            'badAttributeType' => [123],
            'notArray' => 'login',
        ];
    }
}

final class DynamicScenarioModel extends Model
{
    public $login;

    public function scenarios(): array
    {
        return [
            'login' => $this->buildAttributes(),
            'register' => ['login', ...$this->buildExtra()],
            'named' => [$this->buildName()],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildAttributes(): array
    {
        return ['login'];
    }

    /**
     * @return array<int, string>
     */
    private function buildExtra(): array
    {
        return [];
    }

    private function buildName(): string
    {
        return 'login';
    }
}

final class NotInterestingMethod
{
    public function scenarios(): array
    {
        return [
            'unrelated' => ['whatever'],
        ];
    }
}
