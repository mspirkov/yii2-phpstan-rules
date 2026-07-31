<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\BaseObjectInstantiationValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\NoConfigObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\NoParamsObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\PositionalObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\SimpleObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\VariadicObject;
use stdClass;

final class ValidObjectUsage
{
    public function run(): void
    {
        new SimpleObject(['name' => 'x', 'age' => 5]);
        new PositionalObject(5, ['status' => 'active']);
    }
}

final class InvalidObjectUsage
{
    public function run(): void
    {
        new SimpleObject(['namee' => 'x']);
        new SimpleObject(['age' => 'five']);
        new SimpleObject(['name' => 'x', 'oops']);
        new PositionalObject(5, ['statuss' => 'active']);
    }
}

final class SkippedObjectUsage
{
    /**
     * @param array<string, mixed> $dynamicConfig
     */
    public function run(array $dynamicConfig): void
    {
        // Not a checked class.
        new stdClass();

        // Dynamic class expression — not a plain Name.
        $className = SimpleObject::class;
        new $className(['namee' => 'x']);

        // Last constructor parameter isn't named "config".
        new NoConfigObject(5);

        // Constructor takes no parameters at all.
        new NoParamsObject();

        // Last constructor parameter is variadic.
        new VariadicObject(1, 2, 3);

        // Config argument missing entirely (uses the default []).
        new SimpleObject();

        // Config built dynamically — not an array literal.
        new SimpleObject($dynamicConfig);
    }
}
