<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation;

use yii\redis\ActiveRecord;

/**
 * @property int $id
 * @property int $customer_id
 */
final class RedisOrder extends ActiveRecord
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
        return ['id', 'customer_id'];
    }
}
