<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;

final class YiiAppAnalyzer
{
    public function isPropertyFetch(Node $node, Scope $scope): bool
    {
        if (!$node instanceof StaticPropertyFetch) {
            return false;
        }

        if (!$node->class instanceof Name) {
            return false;
        }

        if (!$node->name instanceof Identifier) {
            return false;
        }

        if ($node->name->name !== 'app') {
            return false;
        }

        return $scope->resolveName($node->class) === 'Yii';
    }
}
