<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordConditionValidation;

use yii\mongodb\ActiveRecord;

/**
 * @property string $_id
 * @property int $status
 */
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
        return ['_id', 'status'];
    }
}
