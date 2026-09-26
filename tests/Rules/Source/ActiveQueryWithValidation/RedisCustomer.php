<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation;

use yii\redis\ActiveRecord;

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
        return ['id'];
    }

    public function getOrders()
    {
        return $this->hasMany(RedisOrder::class, ['customer_id' => 'id']);
    }
}
