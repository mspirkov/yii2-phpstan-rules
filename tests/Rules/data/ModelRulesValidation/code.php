<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ModelRulesValidation;

use yii\base\Model;
use yii\validators\RequiredValidator;

final class ValidModel extends Model
{
    public $login;
    public $password;
    public $rememberMe;
    public $status;
    public $email;
    public $tags;

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [['login', 'password'], 'required'],
            ['login', 'trim'],
            ['rememberMe', 'boolean'],
            ['status', 'in', 'range' => ['active', 'blocked']],
            ['email', 'email'],
            ['tags', 'each', 'rule' => ['string', 'max' => 20]],
            ['unknown', 'projectSpecificValidator', 'lenght' => 10],
            ['closure', static function (string $attribute, array $params): void {
            }, 'params' => ['key' => 'value']],
            new RequiredValidator(['attributes' => ['external']]),
        ]);
    }

    public function inlineValidator(string $attribute, array $params): void
    {
    }
}

final class InvalidModel extends Model
{
    public function rules(): array
    {
        return [
            ['login'],
            [100, 'required'],
            [['login', 100], 'required'],
            ['', 'required'],
            ['login', 100],
            ['login', 'string', 'unexpected numeric option'],
            ['login', 'string', 'lenght' => 12],
            ['login', 'filter'],
            ['status', 'in'],
            ['login', 'match'],
            ['tags', 'each'],
            ['password', 'compare', 'operator' => '<>'],
            ['createdAt', 'date', 'type' => 'week'],
            ['ip', 'ip', 'ipv4' => false, 'ipv6' => false],
            ['login', 'match', 'pattern' => '[.'],
            ['status', 'in', 'range' => 'active,blocked'],
            ['tags', 'each', 'rule' => []],
            ['tags', 'each', 'rule' => ['string', 'lenght' => 10]],
            ['login', 'required', 'on' => ['create', 1]],
            'not an array',
            ['unknown', 'projectSpecificValidator', 'lenght' => 10],
        ];
    }
}

final class NotModel
{
    public function rules(): array
    {
        return [
            ['login'],
        ];
    }
}

final class AdditionalValidModel extends Model
{
    public string $validatorType = 'projectSpecificValidator';
    public array $arrayRange = ['active'];
    public \Traversable $traversableRange;

    public function rules(): array
    {
        $closure = static function (): array {
            return [
                ['nested', 'required'],
            ];
        };
        $ignored = $closure;
        $dynamicRule = ['dynamicRule', 'safe'];
        $rules = [
            ['fromUnpack', 'safe'],
        ];
        $attributes = ['name'];
        $options = ['max' => 10];
        $arrayRange = ['active'];
        $traversableRange = new \ArrayIterator(['active']);
        $objectRange = new \stdClass();
        $scenario = $this->scenarioName();
        $scenarioList = ['create'];
        $attribute = $this->attributeName();
        $pattern = $this->pattern();
        $ipv4 = $this->dynamicBoolean();

        return [
            $dynamicRule,
            ...$rules,
            [$attribute, 'required'],
            [['login', ...$attributes], 'required'],
            ['login', 'match', 'pattern' => $pattern],
            ['login', 'match', 'pattern' => '/^[a-z]+$/'],
            ['status', 'in', 'range' => $arrayRange],
            ['status', 'in', 'range' => $this->arrayRange],
            ['status', 'in', 'range' => $traversableRange],
            ['status', 'in', 'range' => $this->traversableRange],
            ['status', 'in', 'range' => $objectRange],
            ['login', 'required', 'on' => 'create'],
            ['login', 'required', 'on' => [...$scenarioList]],
            ['login', 'required', 'except' => $scenario],
            ['login', 'string', ...$options],
            [0 => 'login', 1 => 'required'],
            ['0' => 'login', '1' => 'required'],
            ['login', $this->validatorType],
            ['login', 'inlineValidator'],
            ['login', \yii\validators\StringValidator::class],
            ['ip', 'ip', 'ipv4' => 0, 'ipv6' => false],
            ['ip', 'ip', 'ipv4' => true, 'ipv6' => false],
            ['ip', 'ip', 'ipv4' => $ipv4, 'ipv6' => false],
            ['login', 'string', 'class' => \yii\validators\StringValidator::class],
            ['login', 'string', 'current' => 'value'],
            ['login', 'string', 'on beforeValidate' => static function (): void {
            }],
        ];
    }

    public function inlineValidator(string $attribute, array $params): void
    {
    }

    private function scenarioName(): string
    {
        return 'create';
    }

    private function attributeName(): string
    {
        return 'login';
    }

    private function pattern(): string
    {
        return '/^[a-z]+$/';
    }

    private function dynamicBoolean(): bool
    {
        return (bool) mt_rand(0, 1);
    }
}

final class AdditionalInvalidModel extends Model
{
    public function rules(): array
    {
        $dynamicKey = 'dynamic';

        return [
            [1 => 'required'],
            [null, 'required'],
            ['login', null],
            ['tags', 'each', 'rule' => [null]],
            [[''], 'required'],
            ['login', 'match', 'pattern' => 100],
            ['login', 'required', 'on' => 1],
            [$dynamicKey => 'required'],
        ];
    }
}

final class AdditionalInvalidMergeModel extends Model
{
    public function rules(): array
    {
        return array_merge([], [
            ['mergeLogin'],
        ]);
    }
}

final class DynamicRulesModel extends Model
{
    public function rules(): array
    {
        return parent::rules();
    }
}
