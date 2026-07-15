<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectPropertyAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentConfigMethodAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Model;

/**
 * @implements Rule<ClassMethod>
 */
final class ModelScenariosValidationRule implements Rule
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer;

    private ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer,
        ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->baseObjectPropertyAnalyzer = $baseObjectPropertyAnalyzer;
        $this->componentConfigMethodAnalyzer = $componentConfigMethodAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
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
            'scenarios',
            Model::class,
            fn(Array_ $scenarios, Scope $scope): array => $this->validateScenarios($scenarios, $scope)
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateScenarios(Array_ $scenarios, Scope $scope): array
    {
        $errors = [];
        $classReflection = $scope->getClassReflection();

        foreach ($this->baseObjectConfigAnalyzer->collectStaticItems($scenarios) as $scenarioName => $item) {
            if (!is_string($scenarioName)) {
                continue;
            }

            if ($scenarioName === '') {
                $errors[] = $this->buildError('Model scenario name cannot be empty.', $item);

                continue;
            }

            $errors = array_merge($errors, $this->validateScenarioAttributes(
                $item->value,
                $classReflection,
                $scope
            ));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateScenarioAttributes(
        Expr $attributesExpr,
        ?ClassReflection $classReflection,
        Scope $scope
    ): array {
        if (!$attributesExpr instanceof Array_) {
            return $scope->getType($attributesExpr)->isArray()->no()
                ? [$this->buildError('Model scenario attributes must be an array of strings.', $attributesExpr)]
                : [];
        }

        $errors = [];
        foreach ($attributesExpr->items as $item) {
            if ($item->unpack) {
                continue;
            }

            $errors = array_merge($errors, $this->validateScenarioAttribute(
                $item->value,
                $classReflection,
                $scope
            ));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateScenarioAttribute(
        Expr $attributeExpr,
        ?ClassReflection $classReflection,
        Scope $scope
    ): array {
        if ($attributeExpr instanceof String_) {
            $attributeName = $this->stripUnsafePrefix($attributeExpr->value);
            if ($attributeName === '') {
                return [$this->buildError('Model scenario attribute name cannot be empty.', $attributeExpr)];
            }

            return $this->validateAttributeExists($attributeName, $attributeExpr, $classReflection);
        }

        if ($this->expressionTypeAnalyzer->isDefinitelyNotString($attributeExpr, $scope)) {
            return [$this->buildError('Model scenario attributes must be strings.', $attributeExpr)];
        }

        return [];
    }

    private function stripUnsafePrefix(string $attributeName): string
    {
        return strncmp($attributeName, '!', 1) === 0 ? substr($attributeName, 1) : $attributeName;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeExists(
        string $attributeName,
        Node $node,
        ?ClassReflection $classReflection
    ): array {
        if (
            $classReflection === null
            || !$this->baseObjectPropertyAnalyzer->isUnknownAttribute($classReflection, $attributeName)
        ) {
            return [];
        }

        return [
            $this->buildError(
                sprintf('Unknown attribute "%s" for model %s.', $attributeName, $classReflection->getName()),
                $node
            ),
        ];
    }

    private function buildError(string $message, Node $node): IdentifierRuleError
    {
        return ErrorBuilder::build($message, Identifiers::MODEL_SCENARIOS_VALIDATION, $node->getStartLine());
    }
}
