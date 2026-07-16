<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

final class Order extends ActiveRecord
{
    /**
     * @return ActiveQuery<Item>
     */
    public function getItems()
    {
        return $this->hasMany(Item::class, ['order_id' => 'id']);
    }
}
