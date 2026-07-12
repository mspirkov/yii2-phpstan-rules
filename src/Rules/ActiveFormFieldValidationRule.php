<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectPropertyAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Model;
use yii\widgets\ActiveForm;

/**
 * @implements Rule<MethodCall>
 */
final class ActiveFormFieldValidationRule implements Rule
{
    private BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    public function __construct(
        BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->baseObjectPropertyAnalyzer = $baseObjectPropertyAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
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
        if (!$node->name instanceof Identifier || strtolower($node->name->name) !== 'field') {
            return [];
        }

        if (!$this->expressionTypeAnalyzer->isObjectOf($node->var, $scope, ActiveForm::class)) {
            return [];
        }

        if (!isset($node->args[0], $node->args[1]) || !$node->args[0] instanceof Arg || !$node->args[1] instanceof Arg) {
            return [];
        }

        $modelClassReflection = $this->expressionTypeAnalyzer->getSingleClassReflectionOf(
            $node->args[0]->value,
            $scope,
            Model::class
        );

        if ($modelClassReflection === null) {
            return [];
        }

        $attributeName = $this->expressionValueResolver->getSingleStringValue($node->args[1]->value, $scope);
        if ($attributeName === null) {
            return [];
        }

        if (!$this->baseObjectPropertyAnalyzer->isUnknownAttribute($modelClassReflection, $attributeName)) {
            return [];
        }

        return [
            ErrorBuilder::build(
                sprintf('Unknown attribute "%s" for model %s.', $attributeName, $modelClassReflection->getName()),
                Identifiers::ACTIVE_FORM_FIELD_VALIDATION,
                $node->getStartLine()
            ),
        ];
    }
}
