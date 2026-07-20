<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveFormFieldValidation;

use yii\base\Model;

/**
 * @property string $email
 * @property-read string $fullName
 */
final class ContactModel extends Model
{
    public $name;

    private $phoneNumber;

    public function getPhone(): string
    {
        return $this->phoneNumber;
    }

    public function setPhone(string $phone): void
    {
        $this->phoneNumber = $phone;
    }

    public function getStatus(): string
    {
        return 'active';
    }

    public function setSecret(string $secret): void {}
}
