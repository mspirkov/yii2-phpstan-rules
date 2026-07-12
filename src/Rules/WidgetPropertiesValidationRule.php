<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use MSpirkov\Yii2\PHPStan\Analyzers\BaseObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ComponentObjectConfigAnalyzer;
use MSpirkov\Yii2\PHPStan\Analyzers\ExpressionTypeAnalyzer;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use yii\base\Widget;

/**
 * @implements Rule<StaticCall>
 */
final class WidgetPropertiesValidationRule implements Rule
{
    /** @var list<string> */
    private const CONFIG_METHODS = [
        'begin',
        'widget',
    ];

    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    private ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer;

    private ExpressionTypeAnalyzer $expressionTypeAnalyzer;

    public function __construct(
        BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer,
        ComponentObjectConfigAnalyzer $componentObjectConfigAnalyzer,
        ExpressionTypeAnalyzer $expressionTypeAnalyzer
    ) {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
        $this->componentObjectConfigAnalyzer = $componentObjectConfigAnalyzer;
        $this->expressionTypeAnalyzer = $expressionTypeAnalyzer;
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        if (!$node->name instanceof Identifier || !in_array(strtolower($node->name->name), self::CONFIG_METHODS, true)) {
            return [];
        }

        $widgetClassName = $scope->resolveName($node->class);
        if (!$this->expressionTypeAnalyzer->isClassNameOf($widgetClassName, Widget::class)) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg || !$node->args[0]->value instanceof Array_) {
            return [];
        }

        $items = $this->baseObjectConfigAnalyzer->collectStaticItems($node->args[0]->value);
        $options = $this->baseObjectConfigAnalyzer->collectOptions($items, 0);

        $errors = [];
        foreach ($options['invalidKeys'] as $invalidKey) {
            $errors[] = ErrorBuilder::build(
                'Widget configuration option keys must be strings.',
                Identifiers::WIDGET_PROPERTIES_VALIDATION,
                $invalidKey->getStartLine()
            );
        }

        $errors = array_merge($errors, $this->componentObjectConfigAnalyzer->validateObjectOptionNames(
            $widgetClassName,
            $options['items'],
            $scope,
            'widget',
            Identifiers::WIDGET_PROPERTIES_VALIDATION
        ));

        return array_merge($errors, $this->componentObjectConfigAnalyzer->validateObjectOptionValueTypes(
            $widgetClassName,
            $options['items'],
            $scope,
            'Widget',
            [],
            Identifiers::WIDGET_PROPERTIES_VALIDATION
        ));
    }
}
