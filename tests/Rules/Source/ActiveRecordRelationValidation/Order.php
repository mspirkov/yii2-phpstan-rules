<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $customer_id
 */
final class Order extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'order';
    }
}
