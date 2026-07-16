<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentConfigMethodAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Action;
use yii\base\Controller;

/**
 * @implements Rule<ClassMethod>
 */
final class ControllerActionsValidationRule implements Rule
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer;

    private ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer,
        ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->componentConfigMethodAnalyzer = $componentConfigMethodAnalyzer;
        $this->componentObjectConfigAnalyzer = $componentObjectConfigAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
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
            'actions',
            Controller::class,
            fn(Array_ $actions, Scope $scope): array => $this->validateActionsList($actions, $scope)
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateActionsList(Array_ $actions, Scope $scope): array
    {
        $errors = [];

        foreach ($this->baseObjectConfigAnalyzer->collectStaticItems($actions) as $actionId => $item) {
            if ($actionId === '') {
                $errors[] = $this->buildError('Controller action ID cannot be empty.', $item);

                continue;
            }

            $errors = array_merge($errors, $this->validateActionConfig($item->value, $scope));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateActionConfig(Expr $expr, Scope $scope): array
    {
        if ($expr instanceof Array_) {
            return $this->validateActionArray($expr, $scope);
        }

        return $this->checkActionClass($expr, $scope)['errors'];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateActionArray(Array_ $actionConfig, Scope $scope): array
    {
        $errors = [];
        $items = $this->baseObjectConfigAnalyzer->collectStaticItems($actionConfig);
        $options = $this->baseObjectConfigAnalyzer->collectOptions($items, 0);

        foreach ($options['invalidKeys'] as $invalidKey) {
            $errors[] = $this->buildError('Controller action configuration option keys must be strings.', $invalidKey);
        }

        $classItem = $items['__class'] ?? $items['class'] ?? null;
        if (!$classItem instanceof ArrayItem) {
            return $errors;
        }

        $result = $this->checkActionClass($classItem->value, $scope);
        $errors = array_merge($errors, $result['errors']);
        if ($result['class'] === null) {
            return $errors;
        }

        $errors = array_merge($errors, $this->componentObjectConfigAnalyzer->validateObjectOptionNames(
            $result['class'],
            $options['items'],
            $scope,
            'action',
            Identifiers::CONTROLLER_ACTIONS_VALIDATION
        ));

        return array_merge($errors, $this->componentObjectConfigAnalyzer->validateObjectOptionValueTypes(
            $result['class'],
            $options['items'],
            $scope,
            'Action',
            [],
            Identifiers::CONTROLLER_ACTIONS_VALIDATION
        ));
    }

    /**
     * @return array{class: class-string<Action>|null, errors: list<IdentifierRuleError>}
     */
    private function checkActionClass(Expr $expr, Scope $scope): array
    {
        $className = $this->expressionValueResolver->getSingleStringValue($expr, $scope);
        if ($className === null || !$this->expressionTypeAnalyzer->hasClass($className)) {
            return ['class' => null, 'errors' => []];
        }

        if (!$this->expressionTypeAnalyzer->isClassNameOf($className, Action::class)) {
            return [
                'class' => null,
                'errors' => [
                    $this->buildError(
                        sprintf('Controller action class "%s" must be yii\base\Action or its subclass.', $className),
                        $expr
                    ),
                ],
            ];
        }

        return ['class' => $className, 'errors' => []];
    }

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return ErrorBuilder::build($message, Identifiers::CONTROLLER_ACTIONS_VALIDATION, $node->getStartLine());
    }
}
