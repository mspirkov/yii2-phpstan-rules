<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveQueryWithValidation;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

final class Address extends ActiveRecord
{
    /**
     * @return ActiveQuery<Country>
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }
}
