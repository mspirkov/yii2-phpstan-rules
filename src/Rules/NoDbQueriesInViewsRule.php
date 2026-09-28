<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\DbQueriesUsageAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ViewFileAnalyzer;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node>
 */
final class NoDbQueriesInViewsRule implements Rule
{
    private DbQueriesUsageAnalyzer $dbQueriesUsageAnalyzer;

    private ViewFileAnalyzer $viewFileAnalyzer;

    public function __construct(
        DbQueriesUsageAnalyzer $dbQueriesUsageAnalyzer,
        ViewFileAnalyzer $viewFileAnalyzer
    ) {
        $this->dbQueriesUsageAnalyzer = $dbQueriesUsageAnalyzer;
        $this->viewFileAnalyzer = $viewFileAnalyzer;
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
        if (!$this->viewFileAnalyzer->isViewFile($scope->getFile())) {
            return [];
        }

        if (!$this->dbQueriesUsageAnalyzer->isDbQueriesUsage($node, $scope)) {
            return [];
        }

        return [
            ErrorBuilder::build(
                'Database queries in views are forbidden. Move queries to repositories.',
                Identifiers::NO_DB_QUERIES_IN_VIEWS
            ),
        ];
    }
}
