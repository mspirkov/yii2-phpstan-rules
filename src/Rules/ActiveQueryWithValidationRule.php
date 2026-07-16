<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ActiveRecordRelationAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<MethodCall>
 */
final class ActiveQueryWithValidationRule implements Rule
{
    /** @var list<string> */
    private const VARIADIC_METHODS = ['with'];

    /** @var list<string> */
    private const ALIASED_METHODS = ['joinwith', 'innerjoinwith'];

    private ExpressionValueResolver $expressionValueResolver;

    private ActiveRecordRelationAnalyzer $relationAnalyzer;

    public function __construct(
        ExpressionValueResolver $expressionValueResolver,
        ActiveRecordRelationAnalyzer $relationAnalyzer
    ) {
        $this->expressionValueResolver = $expressionValueResolver;
        $this->relationAnalyzer = $relationAnalyzer;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }

        $methodName = strtolower($node->name->name);
        $isVariadic = in_array($methodName, self::VARIADIC_METHODS, true);
        $isAliased = in_array($methodName, self::ALIASED_METHODS, true);
        if (!$isVariadic && !$isAliased) {
            return [];
        }

        $modelClassReflection = $this->relationAnalyzer->resolveQueryModelClass($scope->getType($node->var));
        if ($modelClassReflection === null) {
            return [];
        }

        $errors = [];
        foreach ($this->collectRelationEntries($node->args, $scope, $isVariadic) as $entry) {
            $path = $isAliased ? $this->stripAlias($entry['name']) : $entry['name'];

            $errors = array_merge(
                $errors,
                $this->validateRelationPath($modelClassReflection, $path, $entry['node'], $scope, $node->name->name)
            );
        }

        return $errors;
    }

    /**
     * @param array<Arg|VariadicPlaceholder> $args
     *
     * @return list<array{name: string, node: Node}>
     */
    private function collectRelationEntries(array $args, Scope $scope, bool $isVariadic): array
    {
        if (!isset($args[0]) || !$args[0] instanceof Arg) {
            return [];
        }

        if ($args[0]->value instanceof Array_) {
            return $this->collectArrayEntries($args[0]->value, $scope);
        }

        if (!$isVariadic) {
            $name = $this->expressionValueResolver->getSingleStringValue($args[0]->value, $scope);

            return $name === null ? [] : [['name' => $name, 'node' => $args[0]->value]];
        }

        $entries = [];
        foreach ($args as $arg) {
            if (!$arg instanceof Arg || $arg->unpack) {
                continue;
            }

            $name = $this->expressionValueResolver->getSingleStringValue($arg->value, $scope);
            if ($name === null) {
                continue;
            }

            $entries[] = ['name' => $name, 'node' => $arg->value];
        }

        return $entries;
    }

    /**
     * @return list<array{name: string, node: Node}>
     */
    private function collectArrayEntries(Array_ $array, Scope $scope): array
    {
        $entries = [];

        foreach ($array->items as $item) {
            if ($item->unpack) {
                continue;
            }

            if ($item->key !== null) {
                $name = $this->expressionValueResolver->getSingleStringValue($item->key, $scope);
                $node = $item->key;
            } else {
                $name = $this->expressionValueResolver->getSingleStringValue($item->value, $scope);
                $node = $item->value;
            }

            if ($name === null) {
                continue;
            }

            $entries[] = ['name' => $name, 'node' => $node];
        }

        return $entries;
    }

    private function stripAlias(string $name): string
    {
        return preg_match('/^(.*?)(?:\s+AS\s+|\s+)(\w+)$/i', $name, $matches) === 1 ? $matches[1] : $name;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateRelationPath(
        ClassReflection $classReflection,
        string $path,
        Node $node,
        Scope $scope,
        string $methodName
    ): array {
        $currentClass = $classReflection;

        foreach (explode('.', $path) as $segment) {
            if ($currentClass === null) {
                return [];
            }

            if (!$this->relationAnalyzer->hasRelation($currentClass, $segment, $scope)) {
                return [
                    ErrorBuilder::build(
                        sprintf(
                            'Unknown relation "%s" for %s in %s() call.',
                            $segment,
                            $currentClass->getName(),
                            $methodName
                        ),
                        Identifiers::ACTIVE_QUERY_WITH_VALIDATION,
                        $node->getStartLine()
                    ),
                ];
            }

            $currentClass = $this->relationAnalyzer->resolveRelatedClass($currentClass, $segment, $scope);
        }

        return [];
    }
}
