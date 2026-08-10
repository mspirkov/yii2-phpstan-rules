<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\QueryAnalyzer;
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

/**
 * @implements Rule<MethodCall>
 */
final class NoDynamicQueryWhereRule implements Rule
{
    /** @var list<string> */
    private const METHODS = ['where', 'andwhere', 'orwhere'];

    private QueryAnalyzer $queryAnalyzer;

    public function __construct(QueryAnalyzer $queryAnalyzer)
    {
        $this->queryAnalyzer = $queryAnalyzer;
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

        if (!in_array(strtolower($node->name->name), self::METHODS, true)) {
            return [];
        }

        if (!$this->queryAnalyzer->isQueryExpression($node->var, $scope)) {
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
                sprintf(
                    'Dynamic string conditions in Query::%s() are forbidden. Use array '
                        . 'condition syntax, for example [\'column\' => $columnValue].',
                    $node->name->name
                ),
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
