<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use MSpirkov\Yii2\PHPStan\Rules\ErrorBuilder;
use MSpirkov\Yii2\PHPStan\Rules\Identifiers;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Type\BooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

final class BaseObjectConfigAnalyzer
{
    /** @var list<string> */
    private const SPECIAL_CONFIG_KEYS = [
        '__class',
        'class',
        'current',
    ];

    private ReflectionProvider $reflectionProvider;

    /** @var array<class-string, array<string, true>> */
    private array $writablePropertiesByClass = [];

    public function __construct(ReflectionProvider $reflectionProvider)
    {
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

    public function getConstantBoolean(Expr $expr, Scope $scope): ?bool
    {
        $type = $scope->getType($expr);

        if ((new BooleanType())->isSuperTypeOf($type)->no()) {
            return null;
        }

        if ($type->isTrue()->yes()) {
            return true;
        }

        if ($type->isFalse()->yes()) {
            return false;
        }

        return null;
    }

    public function getSingleStringValue(Expr $expr, Scope $scope): ?string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        $constantStrings = $scope->getType($expr)->getConstantStrings();
        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }

    public function hasClass(string $className): bool
    {
        return $this->reflectionProvider->hasClass($className);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $parentClass
     *
     * @phpstan-assert-if-true class-string<T> $className
     */
    public function isClassNameOf(string $className, string $parentClass): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return $classReflection->is($parentClass) || $classReflection->isSubclassOf($parentClass);
    }

    public function isDefinitelyNotArrayOrObjectOf(Expr $expr, Scope $scope, string $className): bool
    {
        $type = $scope->getType($expr);
        if (!$type->isArray()->no()) {
            return false;
        }

        return (new ObjectType($className))->isSuperTypeOf($type)->no();
    }

    public function isDefinitelyNotString(Expr $expr, Scope $scope): bool
    {
        return $scope->getType($expr)->isString()->no();
    }

    public function isFunctionCallNamed(FuncCall $funcCall, string $name): bool
    {
        return $funcCall->name instanceof Name && strtolower($funcCall->name->toString()) === $name;
    }

    public function isNullExpression(Expr $expr): bool
    {
        return $expr instanceof ConstFetch && strtolower($expr->name->toString()) === 'null';
    }

    public function isObjectOf(Expr $expr, Scope $scope, string $className): bool
    {
        return (new ObjectType($className))->isSuperTypeOf($scope->getType($expr))->yes();
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
        $writableProperties = $this->getWritableProperties($className);

        foreach ($options as $optionName => $item) {
            if (isset($writableProperties[$optionName])) {
                continue;
            }

            if ($this->isEventOrBehaviorConfigKey($optionName)) {
                continue;
            }

            $errors[] = $this->buildError(
                sprintf('Unknown option "%s" for %s %s.', $optionName, $objectLabel, $className),
                $item,
                $identifier
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

            $errors[] = $this->buildError(
                sprintf(
                    '%s option "%s" for %s must be %s, %s given.',
                    $optionLabel,
                    $optionName,
                    $className,
                    $expectedType->describe(VerbosityLevel::typeOnly()),
                    $actualType->describe(VerbosityLevel::typeOnly())
                ),
                $item->value,
                $identifier
            );
        }

        return $errors;
    }

    /**
     * @param value-of<Identifiers::LIST> $identifier
     */
    private function buildError(string $message, Node $node, string $identifier): IdentifierRuleError
    {
        return ErrorBuilder::build($message, $identifier, $node->getStartLine());
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
     *
     * @return array<string, true>
     */
    private function getWritableProperties(string $className): array
    {
        if (isset($this->writablePropertiesByClass[$className])) {
            return $this->writablePropertiesByClass[$className];
        }

        $properties = array_fill_keys(self::SPECIAL_CONFIG_KEYS, true);
        $reflection = $this->reflectionProvider->getClass($className)->getNativeReflection();
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }

            $properties[$property->getName()] = true;
        }

        foreach ($reflection->getMethods() as $method) {
            $methodName = $method->getName();
            if (
                !$method->isPublic()
                || $method->isStatic()
                || strncmp($methodName, 'set', 3) !== 0
                || strlen($methodName) <= 3
            ) {
                continue;
            }

            $properties[lcfirst(implode('', array_slice(str_split($methodName), 3)))] = true;
        }

        return $this->writablePropertiesByClass[$className] = $properties;
    }

    private function isEventOrBehaviorConfigKey(string $optionName): bool
    {
        return strncmp($optionName, 'on ', 3) === 0 || strncmp($optionName, 'as ', 3) === 0;
    }

    /**
     * @param list<string> $typeCheckSkippedOptions
     */
    private function shouldSkipOptionTypeCheck(string $optionName, array $typeCheckSkippedOptions): bool
    {
        return in_array($optionName, self::SPECIAL_CONFIG_KEYS, true)
            || in_array($optionName, $typeCheckSkippedOptions, true)
            || $this->isEventOrBehaviorConfigKey($optionName);
    }
}
