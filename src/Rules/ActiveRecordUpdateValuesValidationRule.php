<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ActiveRecordAttributeArrayAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\db\BaseActiveRecord;

/**
 * @implements Rule<StaticCall>
 */
final class ActiveRecordUpdateValuesValidationRule implements Rule
{
    /** @var array<string, string> */
    private const VALUE_ARG_LABELS = [
        'updateall' => 'attributes',
        'updateallcounters' => 'counters',
    ];

    private ActiveRecordAttributeArrayAnalyzer $activeRecordAttributeArrayAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ReflectionProvider $reflectionProvider;

    public function __construct(
        ActiveRecordAttributeArrayAnalyzer $activeRecordAttributeArrayAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ReflectionProvider $reflectionProvider
    ) {
        $this->activeRecordAttributeArrayAnalyzer = $activeRecordAttributeArrayAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->reflectionProvider = $reflectionProvider;
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
        if (!array_key_exists($methodName, self::VALUE_ARG_LABELS)) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        $values = $node->args[0]->value;
        if (!$values instanceof Array_) {
            return [];
        }

        $className = $scope->resolveName($node->class);
        if (!$this->expressionTypeAnalyzer->isClassNameOf($className, BaseActiveRecord::class)) {
            return [];
        }

        return $this->activeRecordAttributeArrayAnalyzer->validateAttributeValues(
            $values,
            $this->reflectionProvider->getClass($className),
            $scope,
            $node->name->name . '() ' . self::VALUE_ARG_LABELS[$methodName],
            false,
            Identifiers::ACTIVE_RECORD_UPDATE_VALUES_VALIDATION
        );
    }
}
