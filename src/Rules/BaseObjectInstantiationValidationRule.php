<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\BaseObject;

/**
 * @implements Rule<New_>
 */
final class BaseObjectInstantiationValidationRule implements Rule
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ReflectionProvider $reflectionProvider;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ReflectionProvider $reflectionProvider
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->componentObjectConfigAnalyzer = $componentObjectConfigAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        $className = $scope->resolveName($node->class);
        if (!$this->expressionTypeAnalyzer->isClassNameOf($className, BaseObject::class)) {
            return [];
        }

        $configParamIndex = $this->findConfigParameterIndex($this->reflectionProvider->getClass($className), $node, $scope);
        if ($configParamIndex === null) {
            return [];
        }

        if (!isset($node->args[$configParamIndex]) || !$node->args[$configParamIndex] instanceof Arg) {
            return [];
        }

        $configExpr = $node->args[$configParamIndex]->value;
        if (!$configExpr instanceof Array_) {
            return [];
        }

        return $this->validateConfigArray($configExpr, $className, $scope);
    }

    private function findConfigParameterIndex(ClassReflection $classReflection, New_ $node, Scope $scope): ?int
    {
        $constructor = $classReflection->getConstructor();
        $acceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $constructor->getVariants());
        $parameters = $acceptor->getParameters();

        $lastIndex = count($parameters) - 1;
        if ($lastIndex < 0) {
            return null;
        }

        $lastParameter = $parameters[$lastIndex];
        if ($lastParameter->isVariadic() || $lastParameter->getName() !== 'config') {
            return null;
        }

        return $lastIndex;
    }

    /**
     * @param class-string $objectClass
     *
     * @return list<IdentifierRuleError>
     */
    private function validateConfigArray(Array_ $config, string $objectClass, Scope $scope): array
    {
        $errors = [];
        $items = $this->baseObjectConfigAnalyzer->collectStaticItems($config);
        $options = $this->baseObjectConfigAnalyzer->collectOptions($items, 0);

        foreach ($options['invalidKeys'] as $invalidKey) {
            $errors[] = ErrorBuilder::build(
                'Object configuration option keys must be strings.',
                Identifiers::BASE_OBJECT_INSTANTIATION_VALIDATION,
                $invalidKey->getStartLine()
            );
        }

        $errors = array_merge($errors, $this->componentObjectConfigAnalyzer->validateObjectOptionNames(
            $objectClass,
            $options['items'],
            $scope,
            'object',
            Identifiers::BASE_OBJECT_INSTANTIATION_VALIDATION
        ));

        return array_merge($errors, $this->componentObjectConfigAnalyzer->validateObjectOptionValueTypes(
            $objectClass,
            $options['items'],
            $scope,
            'Object',
            [],
            Identifiers::BASE_OBJECT_INSTANTIATION_VALIDATION
        ));
    }
}
