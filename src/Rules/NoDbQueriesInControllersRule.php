<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\DbQueriesUsageAnalyzer;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Controller;

/**
 * @implements Rule<Node>
 */
final class NoDbQueriesInControllersRule implements Rule
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

        if (!$this->isController($scope->getClassReflection())) {
            return [];
        }

        if (!$this->dbQueriesUsageAnalyzer->isDbQueriesUsage($node, $scope)) {
            return [];
        }

        return [
            ErrorBuilder::build(
                'Database queries in controllers are forbidden. Move queries to repositories.',
                Identifiers::NO_DB_QUERIES_IN_CONTROLLERS
            ),
        ];
    }

    private function isController(ClassReflection $classReflection): bool
    {
        return $classReflection->is(Controller::class) || $classReflection->isSubclassOf(Controller::class);
    }
}
