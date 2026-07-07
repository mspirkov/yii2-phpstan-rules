<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\db\BaseActiveRecord;

/**
 * @implements Rule<MethodCall>
 */
final class ActiveRecordRelationValidationRule implements Rule
{
    /** @var list<string> */
    private const RELATION_METHODS = [
        'hasmany',
        'hasone',
    ];

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    private ReflectionProvider $reflectionProvider;

    public function __construct(
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver,
        ReflectionProvider $reflectionProvider
    ) {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
        $this->reflectionProvider = $reflectionProvider;
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
        if (!in_array($methodName, self::RELATION_METHODS, true)) {
            return [];
        }

        $currentClassReflection = $this->getActiveRecordReceiverClass($node, $scope);
        if (!$currentClassReflection instanceof ClassReflection) {
            return [];
        }

        if (!isset($node->args[0], $node->args[1]) || !$node->args[0] instanceof Arg || !$node->args[1] instanceof Arg) {
            return [];
        }

        $relatedClassName = $this->expressionValueResolver->getSingleStringValue($node->args[0]->value, $scope);
        if ($relatedClassName === null) {
            return [];
        }

        if (!$this->expressionTypeAnalyzer->hasClass($relatedClassName)) {
            return [];
        }

        if (!$this->expressionTypeAnalyzer->isClassNameOf($relatedClassName, BaseActiveRecord::class)) {
            return [];
        }

        if (!$node->args[1]->value instanceof Array_) {
            return [];
        }

        return $this->validateLinkArray(
            $node->args[1]->value,
            $node->name->name,
            $this->reflectionProvider->getClass($relatedClassName),
            $currentClassReflection,
            $scope
        );
    }

    private function getActiveRecordReceiverClass(MethodCall $methodCall, Scope $scope): ?ClassReflection
    {
        $activeRecordReflections = [];

        foreach ($scope->getType($methodCall->var)->getObjectClassReflections() as $classReflection) {
            if ($classReflection->is(BaseActiveRecord::class) || $classReflection->isSubclassOf(BaseActiveRecord::class)) {
                $activeRecordReflections[$classReflection->getName()] = $classReflection;
            }
        }

        if (count($activeRecordReflections) !== 1) {
            return null;
        }

        return array_values($activeRecordReflections)[0];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateLinkArray(
        Array_ $link,
        string $relationMethod,
        ClassReflection $relatedClassReflection,
        ClassReflection $currentClassReflection,
        Scope $scope
    ): array {
        $errors = [];

        foreach ($link->items as $item) {
            if ($item->unpack) {
                continue;
            }

            $relatedProperty = $this->resolveLinkKey($item, $scope);
            $currentProperty = $this->resolveLinkValue($item, $scope);

            if ($relatedProperty === null || $currentProperty === null) {
                continue;
            }

            if (!$relatedClassReflection->hasInstanceProperty($relatedProperty)) {
                $errors[] = $this->buildError(
                    sprintf(
                        'Unknown property "%s" for related ActiveRecord %s in %s() relation link.',
                        $relatedProperty,
                        $relatedClassReflection->getName(),
                        $relationMethod
                    ),
                    $item->key instanceof Expr ? $item->key : $item
                );
            }

            if (!$currentClassReflection->hasInstanceProperty($currentProperty)) {
                $errors[] = $this->buildError(
                    sprintf(
                        'Unknown property "%s" for current ActiveRecord %s in %s() relation link.',
                        $currentProperty,
                        $currentClassReflection->getName(),
                        $relationMethod
                    ),
                    $item->value
                );
            }
        }

        return $errors;
    }

    private function resolveLinkKey(ArrayItem $item, Scope $scope): ?string
    {
        if (!$item->key instanceof Expr) {
            return null;
        }

        return $this->expressionValueResolver->getSingleStringValue($item->key, $scope);
    }

    private function resolveLinkValue(ArrayItem $item, Scope $scope): ?string
    {
        return $this->expressionValueResolver->getSingleStringValue($item->value, $scope);
    }

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return ErrorBuilder::build($message, Identifiers::ACTIVE_RECORD_RELATION_VALIDATION, $node->getStartLine());
    }
}
