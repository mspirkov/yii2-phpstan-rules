<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Finders;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Return_;

final class MethodReturnExpressionFinder extends NodeVisitorAbstract
{
    /** @var list<Expr> */
    private array $returnExpressions = [];

    /**
     * @param array<Node> $nodes
     *
     * @return list<Expr>
     */
    public function find(array $nodes): array
    {
        $this->returnExpressions = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor($this);
        $traverser->traverse($nodes);

        return $this->returnExpressions;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Return_) {
            if ($node->expr instanceof Expr) {
                $this->returnExpressions[] = $node->expr;
            }

            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof FunctionLike || $node instanceof ClassLike) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
