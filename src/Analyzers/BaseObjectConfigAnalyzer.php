<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use MSpirkov\Yii2\PHPStan\Rules\ErrorBuilder;
use MSpirkov\Yii2\PHPStan\Rules\Identifiers;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;

final class BaseObjectConfigAnalyzer
{
    /** @var list<string> */
    private const SPECIAL_CONFIG_KEYS = [
        '__class',
        'class',
    ];

    private ReflectionProvider $reflectionProvider;

    public function __construct(
        ReflectionProvider $reflectionProvider
    ) {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @param array<array-key, ArrayItem> $items
     *
     * @return array{items: array<string, ArrayItem>, invalidKeys: list<ArrayItem>}
     */
    public function collectOptions(array $items, int $firstOptionIndex): array
    {
        $options = [];
        $invalidKeys = [];

        foreach ($items as $key => $item) {
            if (is_int($key)) {
                if ($key >= $firstOptionIndex) {
                    $invalidKeys[] = $item;
                }

                continue;
            }

            $options[$key] = $item;
        }

        return [
            'items' => $options,
            'invalidKeys' => $invalidKeys,
        ];
    }

    /**
     * @return array<array-key, ArrayItem>
     */
    public function collectStaticItems(Array_ $array): array
    {
        $items = [];
        $nextIndex = 0;

        foreach ($array->items as $item) {
            if ($item->unpack) {
                continue;
            }

            $key = $this->getArrayItemKey($item, $nextIndex);
            if (!$key['found']) {
                continue;
            }

            $items[$key['value']] = $item;
            if (is_int($key['value']) && $key['value'] >= $nextIndex) {
                $nextIndex = $key['value'] + 1;
            }
        }

        return $items;
    }

    /**
     * @param class-string $className
     * @param array<string, ArrayItem> $options
     * @param value-of<Identifiers::LIST> $identifier
     *
     * @return list<IdentifierRuleError>
     */
    public function validateObjectOptionNames(
        string $className,
        array $options,
        string $objectLabel,
        string $identifier
    ): array {
        $errors = [];

        foreach ($options as $optionName => $item) {
            if ($this->isWritableOption($className, $optionName)) {
                continue;
            }

            $errors[] = ErrorBuilder::build(
                sprintf('Unknown option "%s" for %s %s.', $optionName, $objectLabel, $className),
                $identifier,
                $item->getStartLine()
            );
        }

        return $errors;
    }

    /**
     * @param class-string $className
     * @param array<string, ArrayItem> $options
     * @param list<string> $typeCheckSkippedOptions
     * @param value-of<Identifiers::LIST> $identifier
     *
     * @return list<IdentifierRuleError>
     */
    public function validateObjectOptionValueTypes(
        string $className,
        array $options,
        Scope $scope,
        string $optionLabel,
        array $typeCheckSkippedOptions,
        string $identifier
    ): array {
        $errors = [];
        $classReflection = $this->reflectionProvider->getClass($className);

        foreach ($options as $optionName => $item) {
            if (
                $this->shouldSkipOptionTypeCheck($optionName, $typeCheckSkippedOptions)
                || !$classReflection->hasInstanceProperty($optionName)
            ) {
                continue;
            }

            $property = $classReflection->getInstanceProperty($optionName, $scope);
            if (!$property->isWritable()) {
                continue;
            }

            $expectedType = $property->getWritableType();
            $actualType = $scope->getType($item->value);
            if ($expectedType instanceof MixedType || $actualType instanceof MixedType) {
                continue;
            }

            if (!$expectedType->accepts($actualType, true)->no()) {
                continue;
            }

            $errors[] = ErrorBuilder::build(
                sprintf(
                    '%s option "%s" for %s must be %s, %s given.',
                    $optionLabel,
                    $optionName,
                    $className,
                    $expectedType->describe(VerbosityLevel::typeOnly()),
                    $actualType->describe(VerbosityLevel::typeOnly())
                ),
                $identifier,
                $item->value->getStartLine(),
            );
        }

        return $errors;
    }

    /**
     * @return array{found: false}|array{found: true, value: int|string}
     */
    private function getArrayItemKey(ArrayItem $item, int $nextIndex): array
    {
        if (!$item->key instanceof Expr) {
            return [
                'found' => true,
                'value' => $nextIndex,
            ];
        }

        if ($item->key instanceof Int_) {
            return [
                'found' => true,
                'value' => $item->key->value,
            ];
        }

        if ($item->key instanceof String_) {
            if (preg_match('/^(0|-?[1-9]\d*)$/', $item->key->value) === 1) {
                return [
                    'found' => true,
                    'value' => (int) $item->key->value,
                ];
            }

            return [
                'found' => true,
                'value' => $item->key->value,
            ];
        }

        return ['found' => false];
    }

    /**
     * @param class-string $className
     */
    private function isWritableOption(string $className, string $propertyName): bool
    {
        if (in_array($propertyName, self::SPECIAL_CONFIG_KEYS, true)) {
            return true;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        $reflection = $classReflection->getNativeReflection();

        if ($reflection->hasProperty($propertyName)) {
            $property = $reflection->getProperty($propertyName);
            if ($property->isPublic() && !$property->isStatic()) {
                return true;
            }
        }

        $setter = 'set' . ucfirst($propertyName);
        if ($reflection->hasMethod($setter)) {
            $method = $reflection->getMethod($setter);
            if ($method->isPublic() && !$method->isStatic()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $typeCheckSkippedOptions
     */
    private function shouldSkipOptionTypeCheck(string $optionName, array $typeCheckSkippedOptions): bool
    {
        return in_array($optionName, self::SPECIAL_CONFIG_KEYS, true)
            || in_array($optionName, $typeCheckSkippedOptions, true);
    }
}
