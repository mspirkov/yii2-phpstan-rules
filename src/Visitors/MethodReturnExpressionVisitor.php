<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Visitors;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Return_;

final class MethodReturnExpressionVisitor extends NodeVisitorAbstract
{
    /** @var list<Expr> */
    private array $expressions = [];

    /**
     * @return list<Expr>
     */
    public function getExpressions(): array
    {
        return $this->expressions;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Return_) {
            if ($node->expr !== null) {
                $this->expressions[] = $node->expr;
            }

            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof FunctionLike || $node instanceof ClassLike) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
