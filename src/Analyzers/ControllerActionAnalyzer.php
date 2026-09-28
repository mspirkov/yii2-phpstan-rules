<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use MSpirkov\Yii2\PHPStan\Finders\MethodReturnExpressionFinder;
use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;

final class ControllerActionAnalyzer
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private ExpressionValueResolver $expressionValueResolver;

    private MethodReturnExpressionFinder $returnExpressionFinder;

    private Parser $parser;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        ExpressionValueResolver $expressionValueResolver,
        MethodReturnExpressionFinder $returnExpressionFinder,
        Parser $parser
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->expressionValueResolver = $expressionValueResolver;
        $this->returnExpressionFinder = $returnExpressionFinder;
        $this->parser = $parser;
    }

    /**
     * @return bool|null `null` when the `actions()` map can't be resolved statically, so the answer is unknown
     */
    public function hasAction(ClassReflection $controller, string $actionId): ?bool
    {
        if ($this->hasInlineAction($controller, $actionId)) {
            return true;
        }

        $mappedActionIds = $this->findMappedActionIds($controller);
        if ($mappedActionIds === null) {
            return null;
        }

        return in_array($actionId, $mappedActionIds, true);
    }

    private function hasInlineAction(ClassReflection $controller, string $actionId): bool
    {
        if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $actionId) !== 1) {
            return false;
        }

        $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId)));
        if (!$controller->hasNativeMethod($methodName)) {
            return false;
        }

        $method = $controller->getNativeMethod($methodName);

        return $method->isPublic() && $method->getName() === $methodName;
    }

    /**
     * @return list<string>|null
     */
    private function findMappedActionIds(ClassReflection $controller): ?array
    {
        $declaringClass = $controller->getNativeMethod('actions')->getDeclaringClass();
        $method = $this->findClassMethod($declaringClass, 'actions');
        if ($method === null) {
            return null;
        }

        $actionIds = [];
        foreach ($this->returnExpressionFinder->find($method->stmts ?? []) as $expression) {
            $expressionActionIds = $this->resolveMappedActionIds($expression, $declaringClass);
            if ($expressionActionIds === null) {
                return null;
            }

            $actionIds = array_merge($actionIds, $expressionActionIds);
        }

        return $actionIds;
    }

    private function findClassMethod(ClassReflection $classReflection, string $methodName): ?ClassMethod
    {
        $fileName = $classReflection->getFileName();
        $classNode = (new NodeFinder())->findFirst(
            $fileName === null ? [] : $this->parser->parseFile($fileName),
            static fn(Node $node): bool => $node instanceof Class_
                && $node->namespacedName !== null
                && $node->namespacedName->toString() === $classReflection->getName()
        );

        return $classNode instanceof Class_ ? $classNode->getMethod($methodName) : null;
    }

    /**
     * @return list<string>|null
     */
    private function resolveMappedActionIds(Expr $expr, ClassReflection $ownerClass): ?array
    {
        if ($expr instanceof Array_) {
            return $this->resolveArrayActionIds($expr, $ownerClass);
        }

        if ($this->isParentActionsCall($expr)) {
            $parentClass = $ownerClass->getParentClass();

            return $parentClass === null ? null : $this->findMappedActionIds($parentClass);
        }

        if ($expr instanceof Plus) {
            return $this->mergeActionIds([
                $this->resolveMappedActionIds($expr->left, $ownerClass),
                $this->resolveMappedActionIds($expr->right, $ownerClass),
            ]);
        }

        if ($expr instanceof FuncCall && $this->expressionValueResolver->isFunctionCallNamed($expr, 'array_merge')) {
            $argumentActionIds = [];
            foreach ($expr->args as $arg) {
                $argumentActionIds[] = $arg instanceof Arg && !$arg->unpack
                    ? $this->resolveMappedActionIds($arg->value, $ownerClass)
                    : null;
            }

            return $this->mergeActionIds($argumentActionIds);
        }

        return null;
    }

    /**
     * @return list<string>|null
     */
    private function resolveArrayActionIds(Array_ $array, ClassReflection $ownerClass): ?array
    {
        $spreadActionIds = [];
        foreach ($array->items as $item) {
            if ($item->unpack) {
                $spreadActionIds[] = $this->resolveMappedActionIds($item->value, $ownerClass);

                continue;
            }

            if ($item->key !== null && !$item->key instanceof String_ && !$item->key instanceof Int_) {
                return null;
            }
        }

        $ownActionIds = array_map('strval', array_keys($this->baseObjectConfigAnalyzer->collectStaticItems($array)));

        return $this->mergeActionIds(array_merge([$ownActionIds], $spreadActionIds));
    }

    /**
     * @param list<list<string>|null> $actionIdLists
     *
     * @return list<string>|null
     */
    private function mergeActionIds(array $actionIdLists): ?array
    {
        $merged = [];
        foreach ($actionIdLists as $actionIds) {
            if ($actionIds === null) {
                return null;
            }

            $merged = array_merge($merged, $actionIds);
        }

        return $merged;
    }

    private function isParentActionsCall(Expr $expr): bool
    {
        return $expr instanceof StaticCall
            && $expr->class instanceof Name
            && $expr->class->toLowerString() === 'parent'
            && $expr->name instanceof Identifier
            && $expr->name->toLowerString() === 'actions';
    }
}
