<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ViewFileAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\View;

/**
 * @implements Rule<MethodCall>
 */
final class ViewRenderExistenceValidationRule implements Rule
{
    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    private ViewFileAnalyzer $viewFileAnalyzer;

    public function __construct(
        ExpressionTypeAnalyzer $expressionTypeAnalyzer,
        ViewFileAnalyzer $viewFileAnalyzer
    ) {
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
        $this->viewFileAnalyzer = $viewFileAnalyzer;
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
        if (!$node->name instanceof Identifier || strtolower($node->name->name) !== 'render') {
            return [];
        }

        if ($this->viewFileAnalyzer->isViewFile($scope->getFile())) {
            return [];
        }

        if (!$this->expressionTypeAnalyzer->isObjectOf($node->var, $scope, View::class)) {
            return [];
        }

        $view = $this->viewFileAnalyzer->getViewName($node, $scope);
        $problem = $view === null ? null : $this->viewFileAnalyzer->findRenderedViewProblem(null, $view);
        if ($problem === null) {
            return [];
        }

        return [
            ErrorBuilder::build(
                $problem['message'],
                Identifiers::VIEW_RENDER_EXISTENCE_VALIDATION,
                $node->getStartLine(),
                $problem['tip']
            ),
        ];
    }
}
