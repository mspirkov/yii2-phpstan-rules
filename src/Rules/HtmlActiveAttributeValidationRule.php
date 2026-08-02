<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectPropertyAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ModelAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\helpers\BaseHtml;

/**
 * @implements Rule<StaticCall>
 */
final class HtmlActiveAttributeValidationRule implements Rule
{
    /** @var array<string, int> */
    private const MODEL_ARG_INDEXES = [
        'activecheckbox' => 0,
        'activecheckboxlist' => 0,
        'activedropdownlist' => 0,
        'activefileinput' => 0,
        'activehiddeninput' => 0,
        'activehint' => 0,
        'activeinput' => 1,
        'activelabel' => 0,
        'activelistbox' => 0,
        'activepasswordinput' => 0,
        'activeradio' => 0,
        'activeradiolist' => 0,
        'activetextarea' => 0,
        'activetextinput' => 0,
    ];

    private BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    private ModelAnalyzer $modelAnalyzer;

    public function __construct(
        BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver,
        ModelAnalyzer $modelAnalyzer
    ) {
        $this->baseObjectPropertyAnalyzer = $baseObjectPropertyAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
        $this->modelAnalyzer = $modelAnalyzer;
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
        if (!$node->class instanceof Name || !$node->name instanceof Identifier) {
            return [];
        }

        $methodName = strtolower($node->name->name);
        if (!array_key_exists($methodName, self::MODEL_ARG_INDEXES)) {
            return [];
        }

        if (!$this->expressionTypeAnalyzer->isClassNameOf($scope->resolveName($node->class), BaseHtml::class)) {
            return [];
        }

        $modelArgIndex = self::MODEL_ARG_INDEXES[$methodName];
        $attributeArgIndex = $modelArgIndex + 1;

        if (
            !isset($node->args[$modelArgIndex], $node->args[$attributeArgIndex])
            || !$node->args[$modelArgIndex] instanceof Arg
            || !$node->args[$attributeArgIndex] instanceof Arg
        ) {
            return [];
        }

        $modelClassReflection = $this->modelAnalyzer->resolveModelClassForAttributeChecks(
            $node->args[$modelArgIndex]->value,
            $scope
        );

        if ($modelClassReflection === null) {
            return [];
        }

        $attributeName = $this->expressionValueResolver->getSingleStringValue(
            $node->args[$attributeArgIndex]->value,
            $scope
        );

        if ($attributeName === null) {
            return [];
        }

        if (!$this->baseObjectPropertyAnalyzer->isUnknownAttribute($modelClassReflection, $attributeName)) {
            return [];
        }

        return [
            ErrorBuilder::build(
                sprintf(
                    'Unknown attribute "%s" for model %s in %s() call.',
                    $attributeName,
                    $modelClassReflection->getName(),
                    $node->name->name
                ),
                Identifiers::HTML_ACTIVE_ATTRIBUTE_VALIDATION,
                $node->getStartLine()
            ),
        ];
    }
}
