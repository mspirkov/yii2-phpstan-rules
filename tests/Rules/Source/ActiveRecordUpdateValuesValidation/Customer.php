<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $status
 * @property int $age
 * @property string $updated_at
 */
final class Customer extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'customer';
    }
}
