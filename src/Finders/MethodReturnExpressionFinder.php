<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Finders;

use MSpirkov\Yii2\PHPStan\Visitors\MethodReturnExpressionVisitor;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;

final class MethodReturnExpressionFinder
{
    /**
     * @param array<Node> $nodes
     *
     * @return list<Expr>
     */
    public function find(array $nodes): array
    {
        $visitor = new MethodReturnExpressionVisitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getExpressions();
    }
}
