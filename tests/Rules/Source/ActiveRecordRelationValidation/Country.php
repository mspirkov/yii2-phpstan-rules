<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 */
final class Country extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'country';
    }
}
