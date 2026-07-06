<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Variable>
 */
final class NoDirectSuperglobalsRule implements Rule
{
    /** @var array<string, string> */
    private const REPLACEMENTS = [
        '_COOKIE' => 'Use yii\web\Request::cookies for reading cookies or yii\web\Response::cookies for writing them instead.',
        '_FILES' => 'Use yii\web\UploadedFile::getInstance() or yii\web\UploadedFile::getInstances() instead.',
        '_GET' => 'Use yii\web\Request::get() or yii\web\Request::getQueryParam() instead.',
        '_POST' => 'Use yii\web\Request::post() or yii\web\Request::getBodyParam() instead.',
        '_REQUEST' => 'Use yii\web\Request and read query or body parameters explicitly instead.',
        '_SERVER' => 'Use yii\web\Request methods such as getHeaders(), getUserAgent(), or getHostInfo() instead.',
        '_SESSION' => 'Use yii\web\Session instead.',
    ];

    public function getNodeType(): string
    {
        return Variable::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!is_string($node->name)) {
            return [];
        }

        if (!isset(self::REPLACEMENTS[$node->name])) {
            return [];
        }

        $message = sprintf(
            'Direct use of superglobal $%s is forbidden. %s',
            $node->name,
            self::REPLACEMENTS[$node->name]
        );

        return [
            ErrorBuilder::build($message, Identifiers::NO_DIRECT_SUPERGLOBALS),
        ];
    }
}
