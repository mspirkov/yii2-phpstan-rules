<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Services;

use PhpParser\Node;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\While_;

final class ActionComplexityAnalyzer
{
    /** @var array<string, int> */
    private array $maxCounts;

    public function __construct(
        int $ifCount = 3,
        int $foreachCount = 0,
        int $forCount = 0,
        int $whileCount = 0,
        int $doWhileCount = 0,
        int $switchCount = 0,
        int $matchCount = 0,
        int $ternaryCount = 0,
        int $tryCatchCount = 0
    ) {
        $this->maxCounts = [
            'ifCount' => $ifCount,
            'foreachCount' => $foreachCount,
            'forCount' => $forCount,
            'whileCount' => $whileCount,
            'doWhileCount' => $doWhileCount,
            'switchCount' => $switchCount,
            'matchCount' => $matchCount,
            'ternaryCount' => $ternaryCount,
            'tryCatchCount' => $tryCatchCount,
        ];
    }

    /**
     * @return array<string, array{actual: int, allowed: int, line: int}>
     */
    public function getExceededLimits(ClassMethod $classMethod): array
    {
        $counts = array_fill_keys(array_keys($this->maxCounts), 0);
        $firstExceededLines = [];

        $this->countNodes($classMethod->stmts ?? [], $counts, $firstExceededLines);

        $violations = [];

        foreach ($this->maxCounts as $counterName => $allowedCount) {
            if ($counts[$counterName] <= $allowedCount) {
                continue;
            }

            $violations[$counterName] = [
                'actual' => $counts[$counterName],
                'allowed' => $allowedCount,
                'line' => $firstExceededLines[$counterName] ?? $classMethod->getLine(),
            ];
        }

        return $violations;
    }

    /**
     * @param array<mixed> $nodes
     * @param array<string, int> $counts
     * @param array<string, int> $firstExceededLines
     */
    private function countNodes(array $nodes, array &$counts, array &$firstExceededLines): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node) {
                $this->countNode($node, $counts, $firstExceededLines);

                foreach (get_object_vars($node) as $subNode) {
                    if ($subNode instanceof Node) {
                        $this->countNodes([$subNode], $counts, $firstExceededLines);

                        continue;
                    }

                    if (is_array($subNode)) {
                        $this->countNodes($subNode, $counts, $firstExceededLines);
                    }
                }
            }
        }
    }

    /**
     * @param array<string, int> $counts
     * @param array<string, int> $firstExceededLines
     */
    private function countNode(Node $node, array &$counts, array &$firstExceededLines): void
    {
        $counterName = $this->getCounterName($node);

        if ($counterName === null) {
            return;
        }

        $counts[$counterName]++;

        if ($counts[$counterName] <= $this->maxCounts[$counterName]) {
            return;
        }

        if (isset($firstExceededLines[$counterName])) {
            return;
        }

        $firstExceededLines[$counterName] = $node->getLine();
    }

    private function getCounterName(Node $node): ?string
    {
        if ($node instanceof If_ || $node instanceof ElseIf_) {
            return 'ifCount';
        }

        if ($node instanceof Foreach_) {
            return 'foreachCount';
        }

        if ($node instanceof For_) {
            return 'forCount';
        }

        if ($node instanceof While_) {
            return 'whileCount';
        }

        if ($node instanceof Do_) {
            return 'doWhileCount';
        }

        if ($node instanceof Switch_) {
            return 'switchCount';
        }

        if ($node instanceof Match_) {
            return 'matchCount';
        }

        if ($node instanceof Ternary) {
            return 'ternaryCount';
        }

        if ($node instanceof TryCatch) {
            return 'tryCatchCount';
        }

        return null;
    }
}
