<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use PHPStan\Reflection\ClassReflection;
use Closure;
use MSpirkov\Yii2\PHPStan\Finders\ModelRulesReturnExpressionFinder;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Closure as ClosureExpr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\BooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use Traversable;
use yii\base\Model;
use yii\captcha\CaptchaValidator;
use yii\validators\BooleanValidator;
use yii\validators\CompareValidator;
use yii\validators\DateValidator;
use yii\validators\DefaultValueValidator;
use yii\validators\EachValidator;
use yii\validators\EmailValidator;
use yii\validators\ExistValidator;
use yii\validators\FileValidator;
use yii\validators\FilterValidator;
use yii\validators\ImageValidator;
use yii\validators\InlineValidator;
use yii\validators\IpValidator;
use yii\validators\NumberValidator;
use yii\validators\RangeValidator;
use yii\validators\RegularExpressionValidator;
use yii\validators\RequiredValidator;
use yii\validators\SafeValidator;
use yii\validators\StringValidator;
use yii\validators\TrimValidator;
use yii\validators\UniqueValidator;
use yii\validators\UrlValidator;
use yii\validators\Validator;

/**
 * @implements Rule<ClassMethod>
 */
final class ModelRulesValidationRule implements Rule
{
    private const VALIDATOR_TYPE_INDEX = 1;

    /** @var array<string, class-string<Validator>> */
    private const BUILT_IN_VALIDATOR_CLASSES = [
        'boolean' => BooleanValidator::class,
        'captcha' => CaptchaValidator::class,
        'compare' => CompareValidator::class,
        'date' => DateValidator::class,
        'datetime' => DateValidator::class,
        'time' => DateValidator::class,
        'default' => DefaultValueValidator::class,
        'double' => NumberValidator::class,
        'each' => EachValidator::class,
        'email' => EmailValidator::class,
        'exist' => ExistValidator::class,
        'file' => FileValidator::class,
        'filter' => FilterValidator::class,
        'image' => ImageValidator::class,
        'in' => RangeValidator::class,
        'integer' => NumberValidator::class,
        'match' => RegularExpressionValidator::class,
        'number' => NumberValidator::class,
        'required' => RequiredValidator::class,
        'safe' => SafeValidator::class,
        'string' => StringValidator::class,
        'trim' => TrimValidator::class,
        'unique' => UniqueValidator::class,
        'url' => UrlValidator::class,
        'ip' => IpValidator::class,
    ];

    /** @var array<string, list<string>> */
    private const REQUIRED_OPTIONS = [
        'each' => ['rule'],
        'filter' => ['filter'],
        'in' => ['range'],
        'match' => ['pattern'],
    ];

    /** @var list<string> */
    private const COMPARE_OPERATORS = ['==', '===', '!=', '!==', '>', '>=', '<', '<='];

    /** @var list<string> */
    private const DATE_TYPES = ['date', 'datetime', 'time'];

    /** @var list<string> */
    private const SPECIAL_CONFIG_KEYS = [
        '__class',
        'class',
        'current',
    ];

    /** @var list<string> */
    private const TYPE_CHECK_SKIPPED_OPTIONS = [
        'except',
        'on',
        'pattern',
        'range',
    ];

    private ReflectionProvider $reflectionProvider;

    private ModelRulesReturnExpressionFinder $returnExpressionFinder;

    /** @var array<class-string<Validator>, array<string, true>> */
    private array $writablePropertiesByClass = [];

    public function __construct(
        ReflectionProvider $reflectionProvider,
        ModelRulesReturnExpressionFinder $returnExpressionFinder
    ) {
        $this->reflectionProvider = $reflectionProvider;
        $this->returnExpressionFinder = $returnExpressionFinder;
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (strtolower($node->name->name) !== 'rules') {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if (
            !$classReflection instanceof ClassReflection
            || (!$classReflection->is(Model::class) && !$classReflection->isSubclassOf(Model::class))
        ) {
            return [];
        }

        $errors = [];
        foreach ($this->returnExpressionFinder->find($node->stmts ?? []) as $returnExpression) {
            foreach ($this->validateRulesReturnExpression($returnExpression, $scope) as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateRulesReturnExpression(Expr $expr, Scope $scope): array
    {
        if ($expr instanceof Array_) {
            return $this->validateRulesList($expr, $scope);
        }

        if ($expr instanceof FuncCall && $this->isFunctionCallNamed($expr, 'array_merge')) {
            $errors = [];
            foreach ($expr->args as $arg) {
                if (!$arg instanceof Arg || !$arg->value instanceof Array_) {
                    continue;
                }

                foreach ($this->validateRulesList($arg->value, $scope) as $error) {
                    $errors[] = $error;
                }
            }

            return $errors;
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateRulesList(Array_ $rules, Scope $scope): array
    {
        $errors = [];

        foreach ($rules->items as $item) {
            if ($item->unpack) {
                continue;
            }

            if ($item->value instanceof Array_) {
                foreach ($this->validateRuleArray($item->value, $scope, false) as $error) {
                    $errors[] = $error;
                }

                continue;
            }

            if ($this->isValidatorObject($item->value, $scope)) {
                continue;
            }

            if ($this->isDefinitelyNotArrayOrValidator($item->value, $scope)) {
                $errors[] = $this->buildError(
                    'Model validation rule must be an array or a yii\validators\Validator instance.',
                    $item->value,
                );
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateRuleArray(Array_ $rule, Scope $scope, bool $embedded): array
    {
        $errors = [];
        $items = $this->collectStaticItems($rule);
        $attributeIndex = $embedded ? null : 0;
        $validatorTypeIndex = $embedded ? 0 : self::VALIDATOR_TYPE_INDEX;
        $firstOptionIndex = $validatorTypeIndex + 1;

        if ($attributeIndex !== null) {
            if (!isset($items[$attributeIndex])) {
                $errors[] = $this->buildError('Model validation rule must specify attribute names at index 0.', $rule);
            } elseif ($this->isNullExpression($items[$attributeIndex]->value)) {
                $errors[] = $this->buildError('Model validation rule attribute names at index 0 cannot be null.', $items[$attributeIndex]->value);
            } else {
                foreach ($this->validateAttributeNames($items[$attributeIndex]->value, $scope) as $error) {
                    $errors[] = $error;
                }
            }
        }

        if (!isset($items[$validatorTypeIndex])) {
            $errors[] = $this->buildError(
                $embedded
                    ? 'Embedded validation rule must specify validator type at index 0.'
                    : 'Model validation rule must specify validator type at index 1.',
                $rule,
            );

            return $errors;
        }

        $validatorTypeExpr = $items[$validatorTypeIndex]->value;
        if ($this->isNullExpression($validatorTypeExpr)) {
            $errors[] = $this->buildError(
                $embedded
                    ? 'Embedded validation rule validator type at index 0 cannot be null.'
                    : 'Model validation rule validator type at index 1 cannot be null.',
                $validatorTypeExpr,
            );

            return $errors;
        }

        if (!$this->isValidValidatorTypeExpression($validatorTypeExpr, $scope)) {
            $errors[] = $this->buildError('Model validation rule validator type must be a string or Closure.', $validatorTypeExpr);

            return $errors;
        }

        $validatorName = $this->getSingleStringValue($validatorTypeExpr, $scope);
        $validatorClass = $this->resolveKnownValidatorClass($validatorTypeExpr, $validatorName, $scope);
        if ($validatorClass === null) {
            return $errors;
        }

        $options = $this->collectOptions($items, $firstOptionIndex);
        foreach ($options['invalidKeys'] as $invalidKey) {
            $errors[] = $this->buildError('Model validation rule option keys must be strings.', $invalidKey);
        }

        foreach ($this->validateOptionNames($validatorClass, $options['items']) as $error) {
            $errors[] = $error;
        }

        foreach ($this->validateOptionValueTypes($validatorClass, $options['items'], $scope) as $error) {
            $errors[] = $error;
        }

        if ($validatorName !== null) {
            foreach ($this->validateRequiredOptions($validatorName, $rule, $options['items']) as $error) {
                $errors[] = $error;
            }
        }

        foreach ($this->validateKnownOptionValues($validatorName, $options['items'], $scope) as $error) {
            $errors[] = $error;
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeNames(Expr $attributesExpr, Scope $scope): array
    {
        if ($attributesExpr instanceof String_) {
            return $attributesExpr->value === ''
                ? [$this->buildError('Model validation rule contains an empty attribute name.', $attributesExpr)]
                : [];
        }

        if ($attributesExpr instanceof Array_) {
            $errors = [];
            foreach ($attributesExpr->items as $item) {
                if ($item->unpack) {
                    continue;
                }

                if ($item->value instanceof String_) {
                    if ($item->value->value === '') {
                        $errors[] = $this->buildError('Model validation rule contains an empty attribute name.', $item->value);
                    }

                    continue;
                }

                if ($this->isDefinitelyNotString($item->value, $scope)) {
                    $errors[] = $this->buildError('Model validation rule attributes must be strings.', $item->value);
                }
            }

            return $errors;
        }

        if ($this->isDefinitelyNotString($attributesExpr, $scope)) {
            return [$this->buildError('Model validation rule attributes must be a string or array of strings.', $attributesExpr)];
        }

        return [];
    }

    /**
     * @param class-string<Validator> $validatorClass
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateOptionNames(string $validatorClass, array $options): array
    {
        $errors = [];
        $writableProperties = $this->getWritableProperties($validatorClass);

        foreach ($options as $optionName => $item) {
            if (isset($writableProperties[$optionName])) {
                continue;
            }

            if (strncmp($optionName, 'on ', 3) === 0 || strncmp($optionName, 'as ', 3) === 0) {
                continue;
            }

            $errors[] = $this->buildError(
                sprintf('Unknown option "%s" for validator %s.', $optionName, $validatorClass),
                $item,
            );
        }

        return $errors;
    }

    /**
     * @param class-string<Validator> $validatorClass
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateOptionValueTypes(string $validatorClass, array $options, Scope $scope): array
    {
        $errors = [];
        $classReflection = $this->reflectionProvider->getClass($validatorClass);

        foreach ($options as $optionName => $item) {
            if ($this->shouldSkipOptionTypeCheck($optionName) || !$classReflection->hasInstanceProperty($optionName)) {
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
                    'Validator option "%s" for %s must be %s, %s given.',
                    $optionName,
                    $validatorClass,
                    $expectedType->describe(VerbosityLevel::typeOnly()),
                    $actualType->describe(VerbosityLevel::typeOnly())
                ),
                $item->value,
            );
        }

        return $errors;
    }

    /**
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateRequiredOptions(string $validatorName, Array_ $rule, array $options): array
    {
        if (!isset(self::REQUIRED_OPTIONS[$validatorName])) {
            return [];
        }

        $errors = [];
        foreach (self::REQUIRED_OPTIONS[$validatorName] as $optionName) {
            if (!isset($options[$optionName]) || $this->isNullExpression($options[$optionName]->value)) {
                $errors[] = $this->buildError(
                    sprintf('Validator "%s" requires option "%s".', $validatorName, $optionName),
                    $rule,
                );
            }
        }

        return $errors;
    }

    /**
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateKnownOptionValues(?string $validatorName, array $options, Scope $scope): array
    {
        if ($validatorName === null) {
            return [];
        }

        $errors = [];

        if ($validatorName === 'compare' && isset($options['operator'])) {
            $operator = $this->getSingleStringValue($options['operator']->value, $scope);
            if ($operator !== null && !in_array($operator, self::COMPARE_OPERATORS, true)) {
                $errors[] = $this->buildError(sprintf('Unknown compare validator operator "%s".', $operator), $options['operator']);
            }
        }

        if (in_array($validatorName, ['date', 'datetime', 'time'], true) && isset($options['type'])) {
            $dateType = $this->getSingleStringValue($options['type']->value, $scope);
            if ($dateType !== null && !in_array($dateType, self::DATE_TYPES, true)) {
                $errors[] = $this->buildError(sprintf('Unknown date validator type "%s".', $dateType), $options['type']);
            }
        }

        if ($validatorName === 'ip' && isset($options['ipv4'], $options['ipv6'])) {
            $ipv4 = $this->getConstantBoolean($options['ipv4']->value, $scope);
            $ipv6 = $this->getConstantBoolean($options['ipv6']->value, $scope);
            if ($ipv4 === false && $ipv6 === false) {
                $errors[] = $this->buildError('IP validator cannot disable both IPv4 and IPv6 checks.', $options['ipv6']);
            }
        }

        if ($validatorName === 'match' && isset($options['pattern'])) {
            foreach ($this->validatePatternOption($options['pattern']->value, $scope) as $error) {
                $errors[] = $error;
            }
        }

        if ($validatorName === 'in' && isset($options['range'])) {
            foreach ($this->validateRangeOption($options['range']->value, $scope) as $error) {
                $errors[] = $error;
            }
        }

        if ($validatorName === 'each' && isset($options['rule']) && $options['rule']->value instanceof Array_) {
            foreach ($this->validateRuleArray($options['rule']->value, $scope, true) as $error) {
                $errors[] = $error;
            }
        }

        foreach (['on', 'except'] as $optionName) {
            if (!isset($options[$optionName])) {
                continue;
            }

            foreach ($this->validateScenarioOption($optionName, $options[$optionName]->value, $scope) as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validatePatternOption(Expr $patternExpr, Scope $scope): array
    {
        $pattern = $this->getSingleStringValue($patternExpr, $scope);
        if ($pattern === null) {
            if ($this->isDefinitelyNotString($patternExpr, $scope)) {
                return [$this->buildError('Match validator option "pattern" must be a string.', $patternExpr)];
            }

            return [];
        }

        if (@preg_match($pattern, '') === false) {
            return [$this->buildError(sprintf('Match validator option "pattern" has an invalid regular expression "%s".', $pattern), $patternExpr)];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateRangeOption(Expr $rangeExpr, Scope $scope): array
    {
        if ($rangeExpr instanceof Array_ || $rangeExpr instanceof ClosureExpr) {
            return [];
        }

        $rangeType = $scope->getType($rangeExpr);
        if ($rangeType->isArray()->yes()) {
            return [];
        }

        if ((new ObjectType(Traversable::class))->isSuperTypeOf($rangeType)->yes()) {
            return [];
        }

        if ($rangeType->isArray()->no() && $rangeType->isObject()->no()) {
            return [$this->buildError('"in" validator option "range" must be an array, Closure, or Traversable.', $rangeExpr)];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateScenarioOption(string $optionName, Expr $optionExpr, Scope $scope): array
    {
        if ($optionExpr instanceof String_) {
            return [];
        }

        if ($optionExpr instanceof Array_) {
            $errors = [];
            foreach ($optionExpr->items as $item) {
                if ($item->unpack) {
                    continue;
                }

                if ($item->value instanceof String_) {
                    continue;
                }

                if ($this->isDefinitelyNotString($item->value, $scope)) {
                    $errors[] = $this->buildError(sprintf('Validator option "%s" must contain only scenario names as strings.', $optionName), $item->value);
                }
            }

            return $errors;
        }

        if ($this->isDefinitelyNotString($optionExpr, $scope)) {
            return [$this->buildError(sprintf('Validator option "%s" must be a string or array of strings.', $optionName), $optionExpr)];
        }

        return [];
    }

    private function shouldSkipOptionTypeCheck(string $optionName): bool
    {
        return in_array($optionName, self::SPECIAL_CONFIG_KEYS, true)
            || in_array($optionName, self::TYPE_CHECK_SKIPPED_OPTIONS, true)
            || strncmp($optionName, 'on ', 3) === 0
            || strncmp($optionName, 'as ', 3) === 0;
    }

    /**
     * @param array<int|string, ArrayItem> $items
     *
     * @return array{items: array<string, ArrayItem>, invalidKeys: list<ArrayItem>}
     */
    private function collectOptions(array $items, int $firstOptionIndex): array
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
     * @return array<int|string, ArrayItem>
     */
    private function collectStaticItems(Array_ $array): array
    {
        $items = [];
        $nextIndex = 0;

        foreach ($array->items as $item) {
            if ($item->unpack) {
                continue;
            }

            $key = $this->getArrayItemKey($item, $nextIndex);
            if ($key === null) {
                continue;
            }

            $items[$key] = $item;
            if (is_int($key) && $key >= $nextIndex) {
                $nextIndex = $key + 1;
            }
        }

        return $items;
    }

    /**
     * @return int|string|null
     */
    private function getArrayItemKey(ArrayItem $item, int $nextIndex)
    {
        if (!$item->key instanceof Expr) {
            return $nextIndex;
        }

        if ($item->key instanceof Int_) {
            return $item->key->value;
        }

        if ($item->key instanceof String_) {
            return $this->normalizeArrayStringKey($item->key->value);
        }

        return null;
    }

    /**
     * @return int|string
     */
    private function normalizeArrayStringKey(string $key)
    {
        if (preg_match('/^(0|-?[1-9]\d*)$/', $key) === 1) {
            return (int) $key;
        }

        return $key;
    }

    /**
     * @return class-string<Validator>|null
     */
    private function resolveKnownValidatorClass(Expr $validatorTypeExpr, ?string $validatorName, Scope $scope): ?string
    {
        if ($validatorTypeExpr instanceof ClosureExpr) {
            return InlineValidator::class;
        }

        if ($validatorName === null) {
            return null;
        }

        if (isset(self::BUILT_IN_VALIDATOR_CLASSES[$validatorName])) {
            return self::BUILT_IN_VALIDATOR_CLASSES[$validatorName];
        }

        if ($this->isValidatorClassName($validatorName)) {
            return $validatorName;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection && $classReflection->hasMethod($validatorName)) {
            return InlineValidator::class;
        }

        return null;
    }

    /**
     * @phpstan-assert-if-true class-string<Validator> $className
     */
    private function isValidatorClassName(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return $classReflection->is(Validator::class) || $classReflection->isSubclassOf(Validator::class);
    }

    private function isValidValidatorTypeExpression(Expr $expr, Scope $scope): bool
    {
        if ($expr instanceof ClosureExpr) {
            return true;
        }

        $type = $scope->getType($expr);
        if ($type->isString()->yes()) {
            return true;
        }

        return (new ObjectType(Closure::class))->isSuperTypeOf($type)->yes();
    }

    private function isValidatorObject(Expr $expr, Scope $scope): bool
    {
        if ($expr instanceof New_ && $expr->class instanceof Name) {
            $type = $scope->getType($expr);

            return (new ObjectType(Validator::class))->isSuperTypeOf($type)->yes();
        }

        return (new ObjectType(Validator::class))->isSuperTypeOf($scope->getType($expr))->yes();
    }

    private function isDefinitelyNotArrayOrValidator(Expr $expr, Scope $scope): bool
    {
        $type = $scope->getType($expr);
        if (!$type->isArray()->no()) {
            return false;
        }

        return (new ObjectType(Validator::class))->isSuperTypeOf($type)->no();
    }

    private function isDefinitelyNotString(Expr $expr, Scope $scope): bool
    {
        return $scope->getType($expr)->isString()->no();
    }

    private function getSingleStringValue(Expr $expr, Scope $scope): ?string
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

    private function getConstantBoolean(Expr $expr, Scope $scope): ?bool
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

    private function isNullExpression(Expr $expr): bool
    {
        return $expr instanceof ConstFetch && strtolower($expr->name->toString()) === 'null';
    }

    /**
     * @param class-string<Validator> $className
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

    private function isFunctionCallNamed(FuncCall $funcCall, string $name): bool
    {
        return $funcCall->name instanceof Name && strtolower($funcCall->name->toString()) === $name;
    }

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier(Identifiers::MODEL_RULES_VALIDATION)
            ->line($node->getStartLine())
            ->build();
    }
}
