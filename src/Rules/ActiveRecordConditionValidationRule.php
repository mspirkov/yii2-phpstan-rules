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
final class ActiveRecordConditionValidationRule implements Rule
{
    /** @var array<string, int> */
    private const CONDITION_ARG_INDEXES = [
        'deleteall' => 0,
        'findall' => 0,
        'findone' => 0,
        'updateall' => 1,
        'updateallcounters' => 1,
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
        if (!array_key_exists($methodName, self::CONDITION_ARG_INDEXES)) {
            return [];
        }

        $className = $scope->resolveName($node->class);
        if (!$this->expressionTypeAnalyzer->isClassNameOf($className, BaseActiveRecord::class)) {
            return [];
        }

        $argIndex = self::CONDITION_ARG_INDEXES[$methodName];
        if (!isset($node->args[$argIndex]) || !$node->args[$argIndex] instanceof Arg) {
            return [];
        }

        $condition = $node->args[$argIndex]->value;
        if (!$condition instanceof Array_) {
            return [];
        }

        return $this->activeRecordAttributeArrayAnalyzer->validateAttributeValues(
            $condition,
            $this->reflectionProvider->getClass($className),
            $scope,
            $node->name->name . '() condition',
            true,
            Identifiers::ACTIVE_RECORD_CONDITION_VALIDATION
        );
    }
}
