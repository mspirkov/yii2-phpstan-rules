<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\BaseObjectInstantiationValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\ConfigProcessingObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\NoConfigObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\NoParamsObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\PositionalObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\SimpleObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\ThirdPartyObject;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\BaseObjectInstantiationValidation\VariadicObject;
use stdClass;

final class ValidObjectUsage
{
    public function run(): void
    {
        new SimpleObject(['name' => 'x', 'age' => 5]);
        new PositionalObject(5, ['status' => 'active']);

        // "extraOption" is configured as skipped for this class — it's consumed by the
        // constructor before parent::__construct(), so it's not a real property.
        new ConfigProcessingObject(['name' => 'x', 'extraOption' => 'y']);

        // This class is configured as fully skipped. "apiKey" is consumed by the constructor
        // (no real property or setter backs it) and "unknownOption" isn't a property at all —
        // both would normally be flagged as unknown options.
        new ThirdPartyObject(['apiKey' => 'secret', 'unknownOption' => 'x']);
    }
}

final class InvalidObjectUsage
{
    public function run(): void
    {
        new SimpleObject(['namee' => 'x']);
        new SimpleObject(['age' => 'five']);
        new SimpleObject(['name' => 'x', 'oops']);
        new SimpleObject(['class' => SimpleObject::class]);
        new PositionalObject(5, ['statuss' => 'active']);

        // Skipping "extraOption" doesn't exempt the class's other, real options.
        new ConfigProcessingObject(['namee' => 'x', 'extraOption' => 'y']);
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
