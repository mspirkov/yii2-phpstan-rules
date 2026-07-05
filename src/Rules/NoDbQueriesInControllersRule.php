<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\DatabaseAccessAnalyzer;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use yii\base\Controller;

/**
 * @implements Rule<Node>
 */
final class NoDbQueriesInControllersRule implements Rule
{
    private DatabaseAccessAnalyzer $databaseAccessAnalyzer;

    public function __construct(DatabaseAccessAnalyzer $databaseAccessAnalyzer)
    {
        $this->databaseAccessAnalyzer = $databaseAccessAnalyzer;
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

        if (!$this->databaseAccessAnalyzer->isDatabaseAccess($node, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Database queries in controllers are forbidden. Move queries to repositories.')
                ->identifier(Identifiers::NO_DB_QUERIES_IN_CONTROLLERS)
                ->build(),
        ];
    }

    private function isController(ClassReflection $classReflection): bool
    {
        return $classReflection->is(Controller::class) || $classReflection->isSubclassOf(Controller::class);
    }
}
