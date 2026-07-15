<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\BaseYii;

/**
 * @implements Rule<StaticCall>
 */
final class CreateObjectValidationRule implements Rule
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->componentObjectConfigAnalyzer = $componentObjectConfigAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        if (!$node->name instanceof Identifier || strtolower($node->name->name) !== 'createobject') {
            return [];
        }

        $className = $scope->resolveName($node->class);
        if (!$this->expressionTypeAnalyzer->isClassNameOf($className, BaseYii::class)) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        $typeExpr = $node->args[0]->value;
        if (!$typeExpr instanceof Array_) {
            return [];
        }

        return $this->validateConfigArray($typeExpr, $scope);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateConfigArray(Array_ $config, Scope $scope): array
    {
        if ($this->expressionTypeAnalyzer->isCallableArray($config, $scope)) {
            return [];
        }

        $errors = [];
        $items = $this->baseObjectConfigAnalyzer->collectStaticItems($config);
        $options = $this->baseObjectConfigAnalyzer->collectOptions($items, 0);

        foreach ($options['invalidKeys'] as $invalidKey) {
            $errors[] = $this->buildError('Yii::createObject() configuration option keys must be strings.', $invalidKey);
        }

        $classItem = $items['__class'] ?? $items['class'] ?? null;
        if (!$classItem instanceof ArrayItem) {
            $errors[] = $this->buildError('Yii::createObject() configuration array must specify "class" or "__class".', $config);

            return $errors;
        }

        $objectClass = $this->resolveObjectClass($classItem->value, $scope);
        if ($objectClass === null) {
            return $errors;
        }

        $errors = array_merge($errors, $this->componentObjectConfigAnalyzer->validateObjectOptionNames(
            $objectClass,
            $options['items'],
            $scope,
            'object',
            Identifiers::CREATE_OBJECT_VALIDATION
        ));

        return array_merge($errors, $this->componentObjectConfigAnalyzer->validateObjectOptionValueTypes(
            $objectClass,
            $options['items'],
            $scope,
            'Object',
            [],
            Identifiers::CREATE_OBJECT_VALIDATION
        ));
    }

    /**
     * @return class-string|null
     */
    private function resolveObjectClass(Expr $expr, Scope $scope): ?string
    {
        $className = $this->expressionValueResolver->getSingleStringValue($expr, $scope);
        if ($className === null) {
            return null;
        }

        if (!$this->expressionTypeAnalyzer->hasClass($className)) {
            return null;
        }

        return $className;
    }

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return ErrorBuilder::build($message, Identifiers::CREATE_OBJECT_VALIDATION, $node->getStartLine());
    }
}
