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
use yii\base\Controller;

/**
 * @implements Rule<MethodCall>
 */
final class ControllerViewExistenceValidationRule implements Rule
{
    /** @var list<string> */
    private const METHODS = ['render', 'renderpartial', 'renderajax'];

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
        if (!$node->name instanceof Identifier || !in_array(strtolower($node->name->name), self::METHODS, true)) {
            return [];
        }

        $controller = $this->expressionTypeAnalyzer->getSingleClassReflectionOf($node->var, $scope, Controller::class);
        if ($controller === null) {
            return [];
        }

        $view = $this->viewFileAnalyzer->getViewName($node, $scope);
        $problem = $view === null ? null : $this->viewFileAnalyzer->findControllerViewProblem($controller, $view);
        if ($problem === null) {
            return [];
        }

        return [
            ErrorBuilder::build(
                $problem['message'],
                Identifiers::CONTROLLER_VIEW_EXISTENCE_VALIDATION,
                $node->getStartLine(),
                $problem['tip']
            ),
        ];
    }
}
