<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentConfigMethodAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ControllerActionAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\ActionFilter;
use yii\base\Controller;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\filters\auth\AuthMethod;
use yii\filters\VerbFilter;

/**
 * @implements Rule<ClassMethod>
 */
final class ControllerBehaviorActionsValidationRule implements Rule
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer;

    private ControllerActionAnalyzer $controllerActionAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer,
        ControllerActionAnalyzer $controllerActionAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->componentConfigMethodAnalyzer = $componentConfigMethodAnalyzer;
        $this->controllerActionAnalyzer = $controllerActionAnalyzer;
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
            'behaviors',
            Controller::class,
            fn(Array_ $behaviors, Scope $scope): array => $this->validateBehaviorsList($behaviors, $scope)
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateBehaviorsList(Array_ $behaviors, Scope $scope): array
    {
        $controller = $scope->getClassReflection();
        if ($controller === null || $controller->isAbstract()) {
            return [];
        }

        $errors = [];
        foreach ($behaviors->items as $item) {
            if ($item->unpack || !$item->value instanceof Array_) {
                continue;
            }

            $errors = array_merge($errors, $this->validateBehaviorConfig($item->value, $controller, $scope));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateBehaviorConfig(Array_ $behaviorConfig, ClassReflection $controller, Scope $scope): array
    {
        $items = $this->baseObjectConfigAnalyzer->collectStaticItems($behaviorConfig);
        $behaviorClass = $this->resolveConfigClass($items, $scope);
        if ($behaviorClass === null) {
            return [];
        }

        if ($this->expressionTypeAnalyzer->isClassNameOf($behaviorClass, VerbFilter::class)) {
            return $this->validateVerbFilterActions($items['actions'] ?? null, $behaviorClass, $controller);
        }

        if (!$this->expressionTypeAnalyzer->isClassNameOf($behaviorClass, ActionFilter::class)) {
            return [];
        }

        $errors = [];
        $actionListOptions = ['only', 'except'];
        if ($this->expressionTypeAnalyzer->isClassNameOf($behaviorClass, AuthMethod::class)) {
            $actionListOptions[] = 'optional';
        }

        foreach ($actionListOptions as $option) {
            $errors = array_merge($errors, $this->validateActionList(
                $items[$option] ?? null,
                sprintf('the "%s" option of %s', $option, $behaviorClass),
                $controller,
                $scope,
                true
            ));
        }

        if ($this->expressionTypeAnalyzer->isClassNameOf($behaviorClass, AccessControl::class)) {
            return array_merge($errors, $this->validateAccessRules(
                $items['rules'] ?? null,
                $behaviorClass,
                $controller,
                $scope
            ));
        }

        return $errors;
    }

    /**
     * @param array<array-key, ArrayItem> $items
     *
     * @return class-string|null
     */
    private function resolveConfigClass(array $items, Scope $scope): ?string
    {
        $classItem = $items['__class'] ?? $items['class'] ?? null;
        if (!$classItem instanceof ArrayItem) {
            return null;
        }

        $className = $this->expressionValueResolver->getSingleStringValue($classItem->value, $scope);

        return $className !== null && $this->expressionTypeAnalyzer->hasClass($className) ? $className : null;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateActionList(
        ?ArrayItem $item,
        string $reference,
        ClassReflection $controller,
        Scope $scope,
        bool $allowWildcards
    ): array {
        if ($item === null || !$item->value instanceof Array_) {
            return [];
        }

        $errors = [];
        foreach ($item->value->items as $actionItem) {
            if ($actionItem->unpack) {
                continue;
            }

            $actionId = $this->expressionValueResolver->getSingleStringValue($actionItem->value, $scope);
            if ($actionId === null || ($allowWildcards && strpbrk($actionId, '*?[') !== false)) {
                continue;
            }

            $error = $this->checkActionExists($actionId, $reference, $controller, $actionItem);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @param class-string $behaviorClass
     *
     * @return list<IdentifierRuleError>
     */
    private function validateAccessRules(
        ?ArrayItem $rulesItem,
        string $behaviorClass,
        ClassReflection $controller,
        Scope $scope
    ): array {
        if ($rulesItem === null || !$rulesItem->value instanceof Array_) {
            return [];
        }

        $errors = [];
        foreach ($rulesItem->value->items as $ruleItem) {
            if ($ruleItem->unpack || !$ruleItem->value instanceof Array_) {
                continue;
            }

            $ruleItems = $this->baseObjectConfigAnalyzer->collectStaticItems($ruleItem->value);
            if (!$this->isAccessRuleConfig($ruleItems, $scope)) {
                continue;
            }

            $errors = array_merge($errors, $this->validateActionList(
                $ruleItems['actions'] ?? null,
                sprintf('an access rule of %s', $behaviorClass),
                $controller,
                $scope,
                false
            ));
        }

        return $errors;
    }

    /**
     * @param array<array-key, ArrayItem> $ruleItems
     */
    private function isAccessRuleConfig(array $ruleItems, Scope $scope): bool
    {
        if (!isset($ruleItems['__class']) && !isset($ruleItems['class'])) {
            return true;
        }

        $ruleClass = $this->resolveConfigClass($ruleItems, $scope);

        return $ruleClass !== null && $this->expressionTypeAnalyzer->isClassNameOf($ruleClass, AccessRule::class);
    }

    /**
     * @param class-string $behaviorClass
     *
     * @return list<IdentifierRuleError>
     */
    private function validateVerbFilterActions(
        ?ArrayItem $actionsItem,
        string $behaviorClass,
        ClassReflection $controller
    ): array {
        if ($actionsItem === null || !$actionsItem->value instanceof Array_) {
            return [];
        }

        $errors = [];
        $reference = sprintf('the "actions" option of %s', $behaviorClass);
        foreach ($this->baseObjectConfigAnalyzer->collectStaticItems($actionsItem->value) as $actionId => $item) {
            // `*` stands for all actions.
            if ($actionId === '*') {
                continue;
            }

            $error = $this->checkActionExists((string) $actionId, $reference, $controller, $item);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function checkActionExists(
        string $actionId,
        string $reference,
        ClassReflection $controller,
        Node $node
    ): ?IdentifierRuleError {
        if ($this->controllerActionAnalyzer->hasAction($controller, $actionId) !== false) {
            return null;
        }

        return ErrorBuilder::build(
            sprintf(
                'Action "%s" referenced in %s does not exist in controller %s.',
                $actionId,
                $reference,
                $controller->getName()
            ),
            Identifiers::CONTROLLER_BEHAVIOR_ACTIONS_VALIDATION,
            $node->getStartLine()
        );
    }
}
