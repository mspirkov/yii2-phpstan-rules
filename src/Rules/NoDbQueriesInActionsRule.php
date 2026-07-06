<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\DbQueriesUsageAnalyzer;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Action;

/**
 * @implements Rule<Node>
 */
final class NoDbQueriesInActionsRule implements Rule
{
    private DbQueriesUsageAnalyzer $dbQueriesUsageAnalyzer;

    public function __construct(DbQueriesUsageAnalyzer $dbQueriesUsageAnalyzer)
    {
        $this->dbQueriesUsageAnalyzer = $dbQueriesUsageAnalyzer;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            return [];
        }

        if (!$this->isAction($scope->getClassReflection())) {
            return [];
        }

        if (!$this->dbQueriesUsageAnalyzer->isDbQueriesUsage($node, $scope)) {
            return [];
        }

        return [
            ErrorBuilder::build(
                'Database queries in actions are forbidden. Move queries to repositories.',
                Identifiers::NO_DB_QUERIES_IN_ACTIONS
            ),
        ];
    }

    private function isAction(ClassReflection $classReflection): bool
    {
        return $classReflection->is(Action::class) || $classReflection->isSubclassOf(Action::class);
    }
}
