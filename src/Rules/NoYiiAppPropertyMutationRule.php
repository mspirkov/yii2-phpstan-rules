<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Services\YiiAppAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Unset_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
final class NoYiiAppPropertyMutationRule implements Rule
{
    /** @var list<string> */
    private const MUTATING_METHODS = [
        'setcomponents',
    ];

    private YiiAppAnalyzer $yiiAppAnalyzer;

    public function __construct(YiiAppAnalyzer $yiiAppAnalyzer)
    {
        $this->yiiAppAnalyzer = $yiiAppAnalyzer;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof Assign || $node instanceof AssignOp) {
            return $this->processMutationTarget($node->var, $scope);
        }

        if ($node instanceof AssignRef) {
            return array_merge(
                $this->processMutationTarget($node->var, $scope),
                $this->processMutationTarget($node->expr, $scope)
            );
        }

        if (
            $node instanceof PreInc
            || $node instanceof PostInc
            || $node instanceof PreDec
            || $node instanceof PostDec
        ) {
            return $this->processMutationTarget($node->var, $scope);
        }

        if ($node instanceof Unset_) {
            $errors = [];

            foreach ($node->vars as $var) {
                $errors = array_merge($errors, $this->processMutationTarget($var, $scope));
            }

            return $errors;
        }

        if ($node instanceof MethodCall) {
            return $this->processMethodCall($node, $scope);
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processMutationTarget(Node $target, Scope $scope): array
    {
        $propertyFetch = $this->findYiiAppPropertyFetch($target, $scope);

        if (!$propertyFetch instanceof PropertyFetch) {
            return [];
        }

        if (!$propertyFetch->name instanceof Identifier) {
            return [
                RuleErrorBuilder::message('Modification of dynamic Yii::$app property is forbidden.')
                    ->identifier(Identifiers::NO_YII_APP_PROPERTY_MUTATION)
                    ->build(),
            ];
        }

        return [
            RuleErrorBuilder::message(sprintf('Modification of Yii::$app->%s is forbidden.', $propertyFetch->name->name))
                ->identifier(Identifiers::NO_YII_APP_PROPERTY_MUTATION)
                ->build(),
        ];
    }

    private function findYiiAppPropertyFetch(Node $target, Scope $scope): ?PropertyFetch
    {
        while ($target instanceof ArrayDimFetch) {
            $target = $target->var;
        }

        if (!$target instanceof PropertyFetch) {
            return null;
        }

        if (!$this->yiiAppAnalyzer->isPropertyFetch($target->var, $scope)) {
            return null;
        }

        return $target;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processMethodCall(MethodCall $methodCall, Scope $scope): array
    {
        if (!$this->yiiAppAnalyzer->isPropertyFetch($methodCall->var, $scope)) {
            return [];
        }

        if (!$methodCall->name instanceof Identifier) {
            return [];
        }

        if (!in_array(strtolower($methodCall->name->name), self::MUTATING_METHODS, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('Call to Yii::$app->%s() is forbidden because it modifies application properties.', $methodCall->name->name))
                ->identifier(Identifiers::NO_YII_APP_PROPERTY_MUTATION)
                ->build(),
        ];
    }
}
