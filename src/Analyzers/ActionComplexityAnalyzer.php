<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

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
    public const COUNTER_NAMES = [
        self::COUNTER_NAME_IF,
        self::COUNTER_NAME_FOREACH,
        self::COUNTER_NAME_FOR,
        self::COUNTER_NAME_WHILE,
        self::COUNTER_NAME_DO_WHILE,
        self::COUNTER_NAME_SWITCH,
        self::COUNTER_NAME_MATCH,
        self::COUNTER_NAME_TERNARY,
        self::COUNTER_NAME_TRY_CATCH,
    ];

    private const COUNTER_NAME_IF = 'ifCount';
    private const COUNTER_NAME_FOREACH = 'foreachCount';
    private const COUNTER_NAME_FOR = 'forCount';
    private const COUNTER_NAME_WHILE = 'whileCount';
    private const COUNTER_NAME_DO_WHILE = 'doWhileCount';
    private const COUNTER_NAME_SWITCH = 'switchCount';
    private const COUNTER_NAME_MATCH = 'matchCount';
    private const COUNTER_NAME_TERNARY = 'ternaryCount';
    private const COUNTER_NAME_TRY_CATCH = 'tryCatchCount';

    /** @var array<value-of<self::COUNTER_NAMES>, int> */
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
            self::COUNTER_NAME_IF => $ifCount,
            self::COUNTER_NAME_FOREACH => $foreachCount,
            self::COUNTER_NAME_FOR => $forCount,
            self::COUNTER_NAME_WHILE => $whileCount,
            self::COUNTER_NAME_DO_WHILE => $doWhileCount,
            self::COUNTER_NAME_SWITCH => $switchCount,
            self::COUNTER_NAME_MATCH => $matchCount,
            self::COUNTER_NAME_TERNARY => $ternaryCount,
            self::COUNTER_NAME_TRY_CATCH => $tryCatchCount,
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
                'line' => $firstExceededLines[$counterName] ?? $classMethod->getStartLine(),
            ];
        }

        return $violations;
    }

    /**
     * @param array<mixed> $nodes
     * @param array<value-of<self::COUNTER_NAMES>, int> $counts
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
     * @param array<value-of<self::COUNTER_NAMES>, int> $counts
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

        $firstExceededLines[$counterName] = $node->getStartLine();
    }

    /**
     * @return value-of<self::COUNTER_NAMES>|null
     */
    private function getCounterName(Node $node): ?string
    {
        if ($node instanceof If_ || $node instanceof ElseIf_) {
            return self::COUNTER_NAME_IF;
        }

        if ($node instanceof Foreach_) {
            return self::COUNTER_NAME_FOREACH;
        }

        if ($node instanceof For_) {
            return self::COUNTER_NAME_FOR;
        }

        if ($node instanceof While_) {
            return self::COUNTER_NAME_WHILE;
        }

        if ($node instanceof Do_) {
            return self::COUNTER_NAME_DO_WHILE;
        }

        if ($node instanceof Switch_) {
            return self::COUNTER_NAME_SWITCH;
        }

        if ($node instanceof Match_) {
            return self::COUNTER_NAME_MATCH;
        }

        if ($node instanceof Ternary) {
            return self::COUNTER_NAME_TERNARY;
        }

        if ($node instanceof TryCatch) {
            return self::COUNTER_NAME_TRY_CATCH;
        }

        return null;
    }
}
