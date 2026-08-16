<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\BehaviorAttributesValidation;

use MSpirkov\Yii2\Db\ActiveRecord\DateTimeBehavior;
use yii\base\DynamicModel;
use yii\base\Model;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\AttributeTypecastBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\SluggableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\BaseActiveRecord;

final class ValidPost extends Model
{
    public $id;
    public $title;
    public $slug;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $updated_by;
    public $views_count;
    public $is_published;

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
            'blameable' => [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            'slug' => [
                'class' => SluggableBehavior::class,
                'attribute' => ['title'],
                'slugAttribute' => 'slug',
            ],
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'views_count' => AttributeTypecastBehavior::TYPE_INTEGER,
                    'is_published' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                ],
            ],
            'datetime' => [
                'class' => DateTimeBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
            'timestampAttributes' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
            ],
            'blameableAttributes' => [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_by', 'updated_by'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => 'updated_by',
                ],
            ],
            'slugAttributes' => [
                'class' => SluggableBehavior::class,
                'attribute' => 'title',
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'slug',
                ],
            ],
            'datetimeAttributes' => [
                'class' => DateTimeBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
            ],
            'genericAttributeBehavior' => [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
            ],
        ];
    }
}

final class InvalidPost extends Model
{
    public $title;

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'createdAt',
                'updatedAtAttribute' => 'updatedAt',
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'createdBy',
                'updatedByAttribute' => 'updatedBy',
            ],
            [
                'class' => SluggableBehavior::class,
                'attribute' => 'titel',
                'slugAttribute' => 'alias',
            ],
            [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'viewsCount' => AttributeTypecastBehavior::TYPE_INTEGER,
                ],
            ],
            [
                'class' => DateTimeBehavior::class,
                'createdAtAttribute' => 'createdAt',
                'updatedAtAttribute' => 'updatedAt',
            ],
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['createdAt', 'updatedAt'],
                ],
            ],
            [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['createdBy', 'updatedBy'],
                ],
            ],
            [
                'class' => SluggableBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'aliass',
                ],
            ],
            [
                'class' => DateTimeBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => 'createdAt',
                ],
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['createdAt', 'updatedAt'],
                ],
            ],
        ];
    }
}

final class SkippedPost extends Model
{
    public string $dynamicClassName;

    public $title;

    public $created_at;

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            ...[],
            [],
            ['class' => $this->dynamicClassName],
            ['class' => 'MissingBehaviorClass'],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => false,
            ],
            ['class' => SluggableBehavior::class],
            [
                'class' => SluggableBehavior::class,
                'attribute' => $this->title,
            ],
            ['class' => AttributeTypecastBehavior::class],
            [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'extra',
                ],
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    ...[],
                    BaseActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                ],
            ],
            [
                'class' => SluggableBehavior::class,
                'attribute' => [
                    ...[],
                    'title',
                ],
            ],
        ];
    }
}

final class SkippedDynamicModel extends DynamicModel
{
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'unknown_column',
            ],
        ];
    }
}
