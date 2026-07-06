<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\DbQueriesUsageAnalyzer;
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
        if (!$this->isViewFile($scope->getFile())) {
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

    private function isViewFile(string $file): bool
    {
        return preg_match('~(?:^|/)views/~', str_replace('\\', '/', $file)) === 1;
    }
}
