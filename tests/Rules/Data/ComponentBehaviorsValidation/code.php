<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ComponentBehaviorsValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ComponentBehaviorsValidation\NotBehavior;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ComponentBehaviorsValidation\ProjectBehavior;
use stdClass;
use yii\base\Component;
use yii\base\Model;

final class ValidComponent extends Component
{
    public string $dynamicBehaviorClass = ProjectBehavior::class;

    public function behaviors(): array
    {
        $dynamicBehavior = ['class' => $this->dynamicBehaviorClass, 'whatever' => 123];
        $dynamicBehaviorClass = $this->getDynamicBehaviorClass();
        $dynamicList = [
            ProjectBehavior::class,
        ];

        return array_merge(parent::behaviors(), [
            ProjectBehavior::class,
            $dynamicBehaviorClass,
            [
                'class' => ProjectBehavior::class,
                'name' => 'audit',
                'enabled' => false,
                'threshold' => 10,
                'mixedValue' => new stdClass(),
            ],
            [
                '__class' => ProjectBehavior::class,
                'class' => NotBehavior::class,
                'name' => 'preferredClassKey',
            ],
            new ProjectBehavior(),
            $dynamicBehavior,
            ...$dynamicList,
        ]);
    }

    private function getDynamicBehaviorClass(): string
    {
        return ProjectBehavior::class;
    }
}

final class ValidModel extends Model
{
    public function behaviors(): array
    {
        return [
            ProjectBehavior::class,
        ];
    }
}

final class InvalidComponent extends Component
{
    public function behaviors(): array
    {
        return [
            NotBehavior::class,
            ['class' => NotBehavior::class],
            ['class' => ProjectBehavior::class, 'unexpected numeric option'],
            ['class' => ProjectBehavior::class, 'unknown' => 1],
            ['class' => ProjectBehavior::class, 'on beforeValidate' => static function (): void {
            }],
            ['class' => ProjectBehavior::class, 'as nested' => ProjectBehavior::class],
            ['class' => ProjectBehavior::class, 'enabled' => 1],
            ['__class' => ProjectBehavior::class, 'enabled' => 'yes'],
            ['class' => ProjectBehavior::class, 'threshold' => 'high'],
        ];
    }
}

final class InvalidMergeComponent extends Component
{
    public function behaviors(): array
    {
        return array_merge([], [
            ['class' => ProjectBehavior::class, 'enabled' => 1],
        ]);
    }
}

final class DynamicBehaviorsComponent extends Component
{
    public function behaviors(): array
    {
        return parent::behaviors();
    }
}

final class NotComponent
{
    public function behaviors(): array
    {
        return [
            100,
        ];
    }
}

final class SkippedComponent extends Component
{
    public function behaviors(): array
    {
        $dynamicArrayBehavior = [
            'class' => ProjectBehavior::class,
            'unknown' => 1,
        ];

        return [
            'MissingBehavior',
            [],
            ['class' => 'MissingBehavior'],
            $dynamicArrayBehavior,
        ];
    }
}
