<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectPropertyAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\DynamicModel;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * @implements Rule<StaticCall>
 */
final class UploadedFileInstanceValidationRule implements Rule
{
    /** @var list<string> */
    private const METHODS = ['getinstance', 'getinstances'];

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

        if (!in_array(strtolower($node->name->name), self::METHODS, true)) {
            return [];
        }

        $className = $scope->resolveName($node->class);
        if (!$this->expressionTypeAnalyzer->isClassNameOf($className, UploadedFile::class)) {
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

        if ($this->expressionTypeAnalyzer->isClassReflectionOf($modelClassReflection, DynamicModel::class)) {
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
                sprintf(
                    'Unknown attribute "%s" for model %s in %s() call.',
                    $attributeName,
                    $modelClassReflection->getName(),
                    $node->name->name
                ),
                Identifiers::UPLOADED_FILE_INSTANCE_VALIDATION,
                $node->getStartLine()
            ),
        ];
    }
}
