<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerActionsValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerActionsValidation\NotAction;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerActionsValidation\ProjectAction;
use yii\base\Controller;

final class ValidController extends Controller
{
    public string $dynamicActionClass = ProjectAction::class;

    public string $unresolvedActionClass;

    public function actions(): array
    {
        $dynamicAction = ['class' => $this->dynamicActionClass, 'whatever' => 123];
        $dynamicList = [
            'listed' => ProjectAction::class,
        ];

        return array_merge(parent::actions(), [
            'plain' => ProjectAction::class,
            '404' => ProjectAction::class,
            'dynamic' => $this->unresolvedActionClass,
            'config' => [
                'class' => ProjectAction::class,
                'view' => 'index',
                'enabled' => false,
                'threshold' => 10,
                'mixedValue' => 123,
            ],
            'preferred' => [
                '__class' => ProjectAction::class,
                'class' => NotAction::class,
                'view' => 'preferredClassKey',
            ],
            'events' => [
                'class' => ProjectAction::class,
                'on beforeRun' => 'someHandler',
                'as auditBehavior' => 'app\behaviors\AuditBehavior',
            ],
            'dynamicConfig' => $dynamicAction,
            ...$dynamicList,
        ]);
    }
}

final class NotController
{
    public function actions(): array
    {
        return [
            100,
        ];
    }
}

final class InvalidController extends Controller
{
    public function actions(): array
    {
        return [
            '' => 'MissingActionClass',
            '404' => NotAction::class,
            'notAction' => NotAction::class,
            'notActionArray' => ['class' => NotAction::class],
            'invalidKey' => ['class' => ProjectAction::class, 'unexpected numeric option'],
            'unknownOption' => ['class' => ProjectAction::class, 'unknown' => 1],
            'wrongEnabled' => ['class' => ProjectAction::class, 'enabled' => 1],
            'wrongEnabledString' => ['__class' => ProjectAction::class, 'enabled' => 'yes'],
            'wrongThreshold' => ['class' => ProjectAction::class, 'threshold' => 'high'],
        ];
    }
}

final class SkippedController extends Controller
{
    public function actions(): array
    {
        return [
            'missing' => 'MissingActionClass',
            'emptyArray' => [],
        ];
    }
}
