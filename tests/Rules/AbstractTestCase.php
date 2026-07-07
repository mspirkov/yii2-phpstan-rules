<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPStan\Rules\Rule;

/**
 * @template TRule of Rule
 *
 * @extends RuleTestCase<TRule>
 */
abstract class AbstractTestCase extends RuleTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../../rules.neon',
        ]);
    }

    /**
     * @return class-string<TRule>
     */
    abstract protected static function getRuleClass(): string;

    protected static function getDataFilePath(string $fileName): string
    {
        $ruleName = self::getRuleName();

        return __DIR__ . "/Data/{$ruleName}/{$fileName}.php";
    }

    protected static function getConfigFilePath(string $fileName): string
    {
        $ruleName = self::getRuleName();

        return __DIR__ . "/Config/{$ruleName}/{$fileName}.neon";
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(static::getRuleClass());
    }

    private static function getRuleName(): string
    {
        $ruleClassParts = explode('\\', static::getRuleClass());

        return (string) substr($ruleClassParts[count($ruleClassParts) - 1], 0, -4);
    }
}
