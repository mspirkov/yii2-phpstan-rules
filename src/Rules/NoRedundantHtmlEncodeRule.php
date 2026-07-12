<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\helpers\BaseHtml;

/**
 * @implements Rule<StaticCall>
 */
final class NoRedundantHtmlEncodeRule implements Rule
{
    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(ExpressionTypeAnalyzer $expressionTypeAnalyzer)
    {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
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
        if (!$node->class instanceof Name) {
            return [];
        }

        if (!$node->name instanceof Identifier || strtolower($node->name->name) !== 'encode') {
            return [];
        }

        if (!$this->expressionTypeAnalyzer->isClassNameOf($scope->resolveName($node->class), BaseHtml::class)) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        if (!$scope->getType($node->args[0]->value)->isNumericString()->yes()) {
            return [];
        }

        return [
            ErrorBuilder::build(
                'Html::encode() call is redundant here. Its argument can never contain characters that need '
                    . 'HTML-entity escaping.',
                Identifiers::NO_REDUNDANT_HTML_ENCODE,
                $node->getStartLine()
            ),
        ];
    }
}
