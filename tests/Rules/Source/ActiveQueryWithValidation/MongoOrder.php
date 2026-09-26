<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation;

use yii\mongodb\ActiveRecord;

final class MongoOrder extends ActiveRecord
{
    public static function collectionName(): string
    {
        return 'order';
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return ['_id', 'customer_id'];
    }
}
