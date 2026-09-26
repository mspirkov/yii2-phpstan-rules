<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordUpdateValuesValidation;

use yii\redis\ActiveRecord;

/**
 * @property int $id
 * @property int $status
 * @property int $age
 */
final class RedisCustomer extends ActiveRecord
{
    /**
     * @return string[]
     */
    public static function primaryKey(): array
    {
        return ['id'];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return ['id', 'status', 'age'];
    }
}
