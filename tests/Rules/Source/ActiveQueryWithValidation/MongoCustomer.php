<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation;

use yii\mongodb\ActiveRecord;

final class MongoCustomer extends ActiveRecord
{
    public static function collectionName(): string
    {
        return 'customer';
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return ['_id'];
    }

    public function getOrders()
    {
        return $this->hasMany(MongoOrder::class, ['customer_id' => '_id']);
    }
}
