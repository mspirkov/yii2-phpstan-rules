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
                ErrorBuilder::build(
                    'Use of dynamic Yii application property is forbidden.',
                    Identifiers::NO_FORBIDDEN_YII_APP_PROPERTIES
                ),
            ];
        }

        $propertyName = $node->name->name;

        if (in_array($propertyName, $this->allowedProperties, true)) {
            return [];
        }

        return [
            ErrorBuilder::build(
                sprintf('Use of Yii application property "%s" is forbidden.', $propertyName),
                Identifiers::NO_FORBIDDEN_YII_APP_PROPERTIES
            ),
        ];
    }
}
