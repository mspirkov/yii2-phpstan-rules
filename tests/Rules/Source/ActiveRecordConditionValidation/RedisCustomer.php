<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation;

use yii\redis\ActiveRecord;

/**
 * @property int $id
 * @property int $status
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
        return ['id', 'status'];
    }
}
