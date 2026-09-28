<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NestedViewExistenceValidationRule;

/**
 * @extends AbstractTestCase<NestedViewExistenceValidationRule>
 */
final class NestedViewExistenceValidationRuleTest extends AbstractTestCase
{
    private const TIP = 'The view path is built from the "@app" alias set in the "mspirkovYii2Rules.aliases" parameter. It may point to the wrong directory.';

    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('views/site/index')],
            [
                [$this->message('_missing'), 18],
                [$this->message('_missing.php'), 19],
                [$this->message('sub/missing'), 20],
                [$this->message('/layouts/missing'), 21],
                [$this->message('//layouts/missing'), 22, self::TIP],
                [$this->message('@app/views/layouts/missing'), 23, self::TIP],
                [$this->message('_missing'), 24],
                [$this->unresolved('@unknown/missing', '@unknown'), 25],
                [$this->message('_missing'), 28],
            ],
        );
    }

    public function testRuleMatchesViewsDirectoryCaseInsensitively(): void
    {
        $this->analyse(
            [self::getDataFilePath('cased/Views/page/index')],
            [
                [$this->message('_missing'), 6],
            ],
        );
    }

    public function testRuleTreatsConfiguredViewPathAsViewsDirectory(): void
    {
        $this->analyse(
            [self::getDataFilePath('templates/site/index')],
            [
                [$this->message('_missing'), 9],
                [$this->message('/site/_missing'), 10],
                [$this->message('/layouts/missing'), 11],
            ],
        );
    }

    public function testRuleSkipsNonViewFiles(): void
    {
        $this->analyse(
            [self::getDataFilePath('not-view')],
            [],
        );
    }

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            self::getConfigFilePath('config'),
        ]);
    }

    protected static function getRuleClass(): string
    {
        return NestedViewExistenceValidationRule::class;
    }

    private function message(string $view): string
    {
        return sprintf('View "%s" does not exist. Check the view name or create the view file.', $view);
    }

    private function unresolved(string $view, string $alias): string
    {
        return sprintf(
            'View "%s" uses the alias "%s" that cannot be resolved. Add it to the "mspirkovYii2Rules.aliases" parameter.',
            $view,
            $alias
        );
    }
}
