<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation;

use yii\mongodb\ActiveRecord;

/**
 * @property string $_id
 * @property string $code
 */
final class MongoCountry extends ActiveRecord
{
    public static function collectionName(): string
    {
        return 'country';
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return ['_id', 'code'];
    }
}
