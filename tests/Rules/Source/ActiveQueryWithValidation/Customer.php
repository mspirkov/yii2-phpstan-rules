<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property-read Address $address
 * @property-read Tag[] $tags
 */
final class Customer extends ActiveRecord
{
    /**
     * @return ActiveQuery<Order>
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['customer_id' => 'id']);
    }

    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    public function getAddress()
    {
        return $this->hasOne(Address::class, ['id' => 'address_id']);
    }

    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id']);
    }

    public function getDisplayName(): string
    {
        return 'customer';
    }
}
