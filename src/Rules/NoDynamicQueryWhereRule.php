<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\db\ActiveQueryInterface;
use yii\db\QueryInterface;

/**
 * @implements Rule<MethodCall>
 */
final class NoDynamicQueryWhereRule implements Rule
{
    /** @var list<class-string> */
    private const QUERY_CLASSES = [
        ActiveQueryInterface::class,
        QueryInterface::class,
    ];

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(ExpressionTypeAnalyzer $expressionTypeAnalyzer)
    {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (strtolower($node->name->name) !== 'where') {
            return [];
        }

        if (!$this->expressionTypeAnalyzer->isTypeAnyOf($scope->getType($node->var), self::QUERY_CLASSES)) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        if (!$this->containsEmbeddedValue($node->args[0]->value)) {
            return [];
        }

        return [
            ErrorBuilder::build(
                'Dynamic string conditions in Query::where() are forbidden. Use array '
                    . 'condition syntax, for example [\'column\' => $columnValue].',
                Identifiers::NO_DYNAMIC_QUERY_WHERE
            ),
        ];
    }

    private function containsEmbeddedValue(Expr $expr): bool
    {
        if ($expr instanceof InterpolatedString) {
            return true;
        }

        if (!$expr instanceof Concat) {
            return false;
        }

        return !$expr->left instanceof String_ || !$expr->right instanceof String_;
    }
}
