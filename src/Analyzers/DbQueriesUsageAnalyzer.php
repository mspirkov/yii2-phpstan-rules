<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;
use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\db\Command;
use yii\db\Connection;
use yii\db\QueryInterface;
use yii\db\Transaction;

final class DbQueriesUsageAnalyzer
{
    /** @var list<string> */
    private const ACTIVE_RECORD_STATIC_METHODS = [
        'deleteall',
        'find',
        'findall',
        'findbysql',
        'findone',
        'getdb',
        'updateall',
        'updateallcounters',
    ];

    /** @var list<string> */
    private const ACTIVE_RECORD_INSTANCE_METHODS = [
        'delete',
        'insert',
        'refresh',
        'save',
        'update',
        'updateattributes',
        'updatecounters',
    ];

    /** @var list<class-string> */
    private const DATABASE_OBJECT_CLASSES = [
        ActiveQueryInterface::class,
        Command::class,
        Connection::class,
        QueryInterface::class,
        Transaction::class,
    ];

    /** @var list<class-string> */
    private const ACTIVE_RECORD_CLASSES = [
        ActiveRecord::class,
        ActiveRecordInterface::class,
    ];

    private YiiAppAnalyzer $yiiAppAnalyzer;

    /** @var list<string> */
    private array $yiiAppDbProperties;

    /**
     * @param list<string> $yiiAppDbProperties
     */
    public function __construct(
        YiiAppAnalyzer $yiiAppAnalyzer,
        array $yiiAppDbProperties
    ) {
        $this->yiiAppAnalyzer = $yiiAppAnalyzer;
        $this->yiiAppDbProperties = array_values(array_unique($yiiAppDbProperties));
    }

    public function isDbQueriesUsage(Node $node, Scope $scope): bool
    {
        if ($node instanceof PropertyFetch) {
            return $this->isYiiAppDbPropertyFetch($node, $scope);
        }

        if ($node instanceof MethodCall) {
            return $this->isDatabaseMethodCall($node, $scope);
        }

        if ($node instanceof StaticCall) {
            return $this->isActiveRecordStaticCall($node, $scope);
        }

        if ($node instanceof New_) {
            return $this->isDatabaseObjectCreation($node, $scope);
        }

        return false;
    }

    private function isDatabaseMethodCall(MethodCall $methodCall, Scope $scope): bool
    {
        $methodName = $methodCall->name instanceof Identifier
            ? strtolower($methodCall->name->name)
            : null;

        if ($methodName !== null && $this->isYiiAppDbMethodCall($methodCall, $methodName, $scope)) {
            return true;
        }

        if ($this->containsDirectDatabaseProducer($methodCall->var, $scope)) {
            return false;
        }

        $receiverType = $scope->getType($methodCall->var);

        if ($this->isTypeAnyOf($receiverType, self::DATABASE_OBJECT_CLASSES)) {
            return true;
        }

        return $methodName !== null
            && in_array($methodName, self::ACTIVE_RECORD_INSTANCE_METHODS, true)
            && $this->isTypeAnyOf($receiverType, self::ACTIVE_RECORD_CLASSES);
    }

    private function isYiiAppDbPropertyFetch(PropertyFetch $propertyFetch, Scope $scope): bool
    {
        if (!$propertyFetch->name instanceof Identifier) {
            return false;
        }

        if (!in_array($propertyFetch->name->name, $this->yiiAppDbProperties, true)) {
            return false;
        }

        return $this->yiiAppAnalyzer->isPropertyFetch($propertyFetch->var, $scope);
    }

    private function isYiiAppDbMethodCall(MethodCall $methodCall, string $methodName, Scope $scope): bool
    {
        if (!$this->yiiAppAnalyzer->isPropertyFetch($methodCall->var, $scope)) {
            return false;
        }

        foreach ($this->yiiAppDbProperties as $yiiAppDbProperty) {
            if ($methodName === 'get' . strtolower($yiiAppDbProperty)) {
                return true;
            }
        }

        if ($methodName !== 'get') {
            return false;
        }

        if (!isset($methodCall->args[0]) || !$methodCall->args[0] instanceof Arg) {
            return false;
        }

        foreach ($this->yiiAppDbProperties as $yiiAppDbProperty) {
            if ($this->isStringArgument($methodCall->args[0], $yiiAppDbProperty, $scope)) {
                return true;
            }
        }

        return false;
    }

    private function isActiveRecordStaticCall(StaticCall $staticCall, Scope $scope): bool
    {
        if (!$staticCall->class instanceof Name) {
            return false;
        }

        if (!$staticCall->name instanceof Identifier) {
            return false;
        }

        if (!in_array(strtolower($staticCall->name->name), self::ACTIVE_RECORD_STATIC_METHODS, true)) {
            return false;
        }

        return $this->isTypeAnyOf($scope->resolveTypeByName($staticCall->class), self::ACTIVE_RECORD_CLASSES);
    }

    private function isDatabaseObjectCreation(New_ $new, Scope $scope): bool
    {
        return $this->isTypeAnyOf($scope->getType($new), self::DATABASE_OBJECT_CLASSES);
    }

    private function containsDirectDatabaseProducer(Node $node, Scope $scope): bool
    {
        if ($node instanceof PropertyFetch) {
            return $this->isYiiAppDbPropertyFetch($node, $scope)
                || $this->containsDirectDatabaseProducer($node->var, $scope);
        }

        if ($node instanceof MethodCall) {
            if (
                $node->name instanceof Identifier
                && $this->isYiiAppDbMethodCall($node, strtolower($node->name->name), $scope)
            ) {
                return true;
            }

            if ($this->isTypeAnyOf($scope->getType($node->var), self::DATABASE_OBJECT_CLASSES)) {
                return true;
            }

            return $this->containsDirectDatabaseProducer($node->var, $scope);
        }

        if ($node instanceof StaticCall) {
            return $this->isActiveRecordStaticCall($node, $scope);
        }

        if ($node instanceof New_) {
            return $this->isDatabaseObjectCreation($node, $scope);
        }

        if ($node instanceof ArrayDimFetch) {
            return $this->containsDirectDatabaseProducer($node->var, $scope);
        }

        return false;
    }

    /**
     * @param list<class-string> $classNames
     */
    private function isTypeAnyOf(Type $type, array $classNames): bool
    {
        foreach ($type->getObjectClassReflections() as $classReflection) {
            foreach ($classNames as $className) {
                if (
                    $classReflection->is($className)
                    || $classReflection->isSubclassOf($className)
                    || $classReflection->implementsInterface($className)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isStringArgument(Arg $arg, string $expectedValue, Scope $scope): bool
    {
        foreach ($scope->getType($arg->value)->getConstantStrings() as $constantString) {
            if ($constantString->getValue() === $expectedValue) {
                return true;
            }
        }

        return false;
    }
}
