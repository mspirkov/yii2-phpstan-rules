<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\YiiAppAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
final class NoForbiddenYiiAppPropertiesRule implements Rule
{
    private YiiAppAnalyzer $yiiAppAnalyzer;

    /** @var list<string> */
    private array $allowedProperties;

    /**
     * @param list<string> $allowedProperties
     */
    public function __construct(
        YiiAppAnalyzer $yiiAppAnalyzer,
        array $allowedProperties = []
    ) {
        $this->yiiAppAnalyzer = $yiiAppAnalyzer;
        $this->allowedProperties = $allowedProperties;
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
        if (!$node instanceof PropertyFetch) {
            return [];
        }

        if (!$this->yiiAppAnalyzer->isPropertyFetch($node->var, $scope)) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [
                RuleErrorBuilder::message('Use of dynamic Yii::$app property is forbidden.')
                    ->identifier(Identifiers::NO_FORBIDDEN_YII_APP_PROPERTIES)
                    ->build(),
            ];
        }

        $propertyName = $node->name->name;

        if (in_array($propertyName, $this->allowedProperties, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('Use of Yii::$app->%s is forbidden.', $propertyName))
                ->identifier(Identifiers::NO_FORBIDDEN_YII_APP_PROPERTIES)
                ->build(),
        ];
    }
}
