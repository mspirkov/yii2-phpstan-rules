<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use Closure;
use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentConfigMethodAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Closure as ClosureExpr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;
use Traversable;
use yii\base\Model;
use yii\validators\InlineValidator;
use yii\validators\Validator;

/**
 * @implements Rule<ClassMethod>
 */
final class ModelRulesValidationRule implements Rule
{
    private const VALIDATOR_TYPE_INDEX = 1;

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
    private const TYPE_CHECK_SKIPPED_OPTIONS = [
        'except',
        'on',
        'pattern',
        'range',
    ];

    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer;

    private ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    /** @var array<string, string> */
    private array $customValidators;

    /**
     * @param array<string, string> $customValidators
     */
    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer,
        ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver,
        array $customValidators
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->componentConfigMethodAnalyzer = $componentConfigMethodAnalyzer;
        $this->componentObjectConfigAnalyzer = $componentObjectConfigAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
        $this->customValidators = $customValidators;
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->componentConfigMethodAnalyzer->analyze(
            $node,
            $scope,
            'rules',
            Model::class,
            fn(Array_ $rules, Scope $scope): array => $this->validateRulesList($rules, $scope)
        );
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

            if ($this->expressionTypeAnalyzer->isObjectOf($item->value, $scope, Validator::class)) {
                continue;
            }

            if ($this->expressionTypeAnalyzer->isDefinitelyNotArrayOrObjectOf($item->value, $scope, Validator::class)) {
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
        $items = $this->baseObjectConfigAnalyzer->collectStaticItems($rule);
        $attributeIndex = $embedded ? null : 0;
        $validatorTypeIndex = $embedded ? 0 : self::VALIDATOR_TYPE_INDEX;
        $firstOptionIndex = $validatorTypeIndex + 1;

        if ($attributeIndex !== null) {
            if (!isset($items[$attributeIndex])) {
                $errors[] = $this->buildError('Model validation rule must specify attribute names at index 0.', $rule);
            } elseif ($this->expressionValueResolver->isNullExpression($items[$attributeIndex]->value)) {
                $errors[] = $this->buildError(
                    'Model validation rule attribute names at index 0 cannot be null.',
                    $items[$attributeIndex]->value
                );
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
        if ($this->expressionValueResolver->isNullExpression($validatorTypeExpr)) {
            $errors[] = $this->buildError(
                $embedded
                    ? 'Embedded validation rule validator type at index 0 cannot be null.'
                    : 'Model validation rule validator type at index 1 cannot be null.',
                $validatorTypeExpr,
            );

            return $errors;
        }

        if (!$this->isValidValidatorTypeExpression($validatorTypeExpr, $scope)) {
            $errors[] = $this->buildError(
                'Model validation rule validator type must be a string or Closure.',
                $validatorTypeExpr
            );

            return $errors;
        }

        $validatorName = $this->expressionValueResolver->getSingleStringValue($validatorTypeExpr, $scope);
        $validatorClass = $this->resolveKnownValidatorClass($validatorTypeExpr, $validatorName, $scope);
        if ($validatorClass === null) {
            if ($validatorName !== null) {
                $errors[] = $this->buildError(
                    sprintf('Unknown validator "%s".', $validatorName),
                    $validatorTypeExpr
                );
            }

            return $errors;
        }

        $options = $this->baseObjectConfigAnalyzer->collectOptions($items, $firstOptionIndex);
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
            if ($attributesExpr->value === '') {
                return [$this->buildError('Model validation rule contains an empty attribute name.', $attributesExpr)];
            }

            return $this->validateAttributeExists($attributesExpr->value, $attributesExpr, $scope);
        }

        if ($attributesExpr instanceof Array_) {
            $errors = [];
            foreach ($attributesExpr->items as $item) {
                if ($item->unpack) {
                    continue;
                }

                if ($item->value instanceof String_) {
                    if ($item->value->value === '') {
                        $errors[] = $this->buildError(
                            'Model validation rule contains an empty attribute name.',
                            $item->value
                        );
                    } else {
                        foreach ($this->validateAttributeExists($item->value->value, $item->value, $scope) as $error) {
                            $errors[] = $error;
                        }
                    }

                    continue;
                }

                if ($this->expressionTypeAnalyzer->isDefinitelyNotString($item->value, $scope)) {
                    $errors[] = $this->buildError(
                        'Model validation rule attributes must be strings.',
                        $item->value
                    );
                }
            }

            return $errors;
        }

        if ($this->expressionTypeAnalyzer->isDefinitelyNotStringOrArrayOfStrings($attributesExpr, $scope)) {
            return [
                $this->buildError(
                    'Model validation rule attributes must be a string or array of strings.',
                    $attributesExpr
                ),
            ];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeExists(string $attributeName, Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection || $classReflection->hasInstanceProperty($attributeName)) {
            return [];
        }

        return [
            $this->buildError(
                sprintf('Unknown attribute "%s" for model %s.', $attributeName, $classReflection->getName()),
                $node
            ),
        ];
    }

    /**
     * @param class-string<Validator> $validatorClass
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateOptionNames(string $validatorClass, array $options): array
    {
        return $this->componentObjectConfigAnalyzer->validateObjectOptionNames(
            $validatorClass,
            $options,
            'validator',
            Identifiers::MODEL_RULES_VALIDATION
        );
    }

    /**
     * @param class-string<Validator> $validatorClass
     * @param array<string, ArrayItem> $options
     *
     * @return list<IdentifierRuleError>
     */
    private function validateOptionValueTypes(string $validatorClass, array $options, Scope $scope): array
    {
        return $this->componentObjectConfigAnalyzer->validateObjectOptionValueTypes(
            $validatorClass,
            $options,
            $scope,
            'Validator',
            self::TYPE_CHECK_SKIPPED_OPTIONS,
            Identifiers::MODEL_RULES_VALIDATION
        );
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
            if (
                !isset($options[$optionName])
                || $this->expressionValueResolver->isNullExpression($options[$optionName]->value)
            ) {
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
            $operator = $this->expressionValueResolver->getSingleStringValue($options['operator']->value, $scope);
            if ($operator !== null && !in_array($operator, self::COMPARE_OPERATORS, true)) {
                $errors[] = $this->buildError(
                    sprintf('Unknown compare validator operator "%s".', $operator),
                    $options['operator']
                );
            }
        }

        if (in_array($validatorName, ['date', 'datetime', 'time'], true) && isset($options['type'])) {
            $dateType = $this->expressionValueResolver->getSingleStringValue($options['type']->value, $scope);
            if ($dateType !== null && !in_array($dateType, self::DATE_TYPES, true)) {
                $errors[] = $this->buildError(
                    sprintf('Unknown date validator type "%s".', $dateType),
                    $options['type']
                );
            }
        }

        if ($validatorName === 'ip' && isset($options['ipv4'], $options['ipv6'])) {
            $ipv4 = $this->expressionValueResolver->getConstantBoolean($options['ipv4']->value, $scope);
            $ipv6 = $this->expressionValueResolver->getConstantBoolean($options['ipv6']->value, $scope);
            if ($ipv4 === false && $ipv6 === false) {
                $errors[] = $this->buildError(
                    'IP validator cannot disable both IPv4 and IPv6 checks.',
                    $options['ipv6']
                );
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
        $pattern = $this->expressionValueResolver->getSingleStringValue($patternExpr, $scope);
        if ($pattern === null) {
            if ($this->expressionTypeAnalyzer->isDefinitelyNotString($patternExpr, $scope)) {
                return [$this->buildError('Match validator option "pattern" must be a string.', $patternExpr)];
            }

            return [];
        }

        if (@preg_match($pattern, '') === false) {
            return [
                $this->buildError(
                    sprintf('Match validator option "pattern" has an invalid regular expression "%s".', $pattern),
                    $patternExpr
                ),
            ];
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
            return [
                $this->buildError(
                    '"in" validator option "range" must be an array, Closure, or Traversable.',
                    $rangeExpr
                ),
            ];
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

                if ($this->expressionTypeAnalyzer->isDefinitelyNotString($item->value, $scope)) {
                    $errors[] = $this->buildError(
                        sprintf('Validator option "%s" must contain only scenario names as strings.', $optionName),
                        $item->value
                    );
                }
            }

            return $errors;
        }

        if ($this->expressionTypeAnalyzer->isDefinitelyNotStringOrArrayOfStrings($optionExpr, $scope)) {
            return [
                $this->buildError(
                    sprintf('Validator option "%s" must be a string or array of strings.', $optionName),
                    $optionExpr
                ),
            ];
        }

        return [];
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

        $builtInValidator = Validator::$builtInValidators[$validatorName] ?? null;
        if ($builtInValidator !== null) {
            $builtInValidatorClass = is_array($builtInValidator) && array_key_exists('class', $builtInValidator)
                ? $builtInValidator['class']
                : $builtInValidator;

            if (
                is_string($builtInValidatorClass)
                && $this->expressionTypeAnalyzer->isClassNameOf($builtInValidatorClass, Validator::class)
            ) {
                return $builtInValidatorClass;
            }

            return null;
        }

        if (isset($this->customValidators[$validatorName])) {
            $customValidatorClass = $this->customValidators[$validatorName];
            if ($this->expressionTypeAnalyzer->isClassNameOf($customValidatorClass, Validator::class)) {
                return $customValidatorClass;
            }
        }

        if ($this->expressionTypeAnalyzer->isClassNameOf($validatorName, Validator::class)) {
            return $validatorName;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection && $classReflection->hasMethod($validatorName)) {
            return InlineValidator::class;
        }

        return null;
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

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return ErrorBuilder::build($message, Identifiers::MODEL_RULES_VALIDATION, $node->getStartLine());
    }
}
