<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ActiveRecordRelationValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation\Country;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation\Order;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation\RelationFactory;
use stdClass;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $country_id
 * @property string $country_code
 */
final class Customer extends ActiveRecord
{
    public function validRelations(): void
    {
        $this->hasOne(Country::class, ['id' => 'country_id']);
        $this->hasOne(Country::class, ['code' => 'country_code']);
        $this->hasMany(Order::class, ['customer_id' => 'id']);

        $relatedProperty = 'id';
        $currentProperty = 'country_id';
        $this->hasOne(Country::class, [$relatedProperty => $currentProperty]);

        $dynamicLink = ['missing_id' => 'missing_country_id'];
        $this->hasOne(Country::class, $dynamicLink);

        $method = 'hasOne';
        $this->$method(Country::class, ['id' => 'country_id']);

        $this->save();
        $this->hasOne(Country::class);

        $dynamicRelatedClass = $this->getDynamicRelatedClassName();
        $this->hasOne($dynamicRelatedClass, ['id' => 'country_id']);

        $this->hasOne(Country::class, [...['id' => 'country_id']]);
        (new RelationFactory())->hasOne(Country::class, ['id' => 'country_id']);
    }

    public function invalidRelations(): void
    {
        $this->hasOne(Country::class, ['missing_id' => 'country_id']);
        $this->hasOne(Country::class, ['id' => 'missing_country_id']);
        $this->hasMany(Order::class, ['missing_customer_id' => 'id', 'customer_id' => 'missing_id']);
        $this->hasOne(Country::class, [100 => 'country_id']);
        $this->hasOne(Country::class, ['id' => 100]);
        $this->hasOne(Country::class, ['country_id']);
        $this->hasOne('MissingRecord', ['id' => 'country_id']);
        $this->hasOne(stdClass::class, ['id' => 'country_id']);
    }

    private function getDynamicRelatedClassName(): string
    {
        return Country::class;
    }
}
