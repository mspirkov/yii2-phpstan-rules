<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $email
 * @property int $status
 * @property int $age
 * @property-read string $displayName
 * @property mixed $extra
 */
final class Customer extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'customer';
    }

    public function getFullName(): string
    {
        return $this->email;
    }

    public function setFullName(string $value): void
    {
        $this->email = $value;
    }
}
