<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectPropertyAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentConfigMethodAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Model;

/**
 * @implements Rule<ClassMethod>
 */
final class ModelAttributeHintsValidationRule implements Rule
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer;

    private ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        BaseObjectPropertyAnalyzer $baseObjectPropertyAnalyzer,
        ComponentConfigMethodAnalyzer $componentConfigMethodAnalyzer
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->baseObjectPropertyAnalyzer = $baseObjectPropertyAnalyzer;
        $this->componentConfigMethodAnalyzer = $componentConfigMethodAnalyzer;
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
            'attributeHints',
            Model::class,
            fn(Array_ $hints, Scope $scope): array => $this->validateAttributeHints($hints, $scope)
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeHints(Array_ $hints, Scope $scope): array
    {
        $errors = [];

        foreach ($this->baseObjectConfigAnalyzer->collectStaticItems($hints) as $attributeName => $item) {
            if (!is_string($attributeName)) {
                continue;
            }

            if ($attributeName === '') {
                $errors[] = $this->buildError('Model attribute hint contains an empty attribute name.', $item);

                continue;
            }

            $errors = array_merge($errors, $this->validateAttributeExists($attributeName, $item, $scope));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateAttributeExists(string $attributeName, Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
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
        return ErrorBuilder::build($message, Identifiers::MODEL_ATTRIBUTE_HINTS_VALIDATION, $node->getStartLine());
    }
}
