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
                [$this->message('_missing', 'views/site/_missing'), 18],
                [$this->message('_missing.php', 'views/site/_missing'), 19],
                [$this->message('sub/missing', 'views/site/sub/missing'), 20],
                [$this->message('/layouts/missing', 'views/layouts/missing'), 21],
                [$this->message('//layouts/missing', 'views/layouts/missing', true), 22, self::TIP],
                [$this->message('@app/views/layouts/missing', 'views/layouts/missing', true), 23, self::TIP],
                [$this->message('_missing', 'views/site/_missing'), 24],
                [$this->unresolved('@unknown/missing', '@unknown'), 25],
                [$this->message('_missing', 'views/site/_missing'), 28],
            ],
        );
    }

    public function testRuleMatchesViewsDirectoryCaseInsensitively(): void
    {
        $this->analyse(
            [self::getDataFilePath('cased/Views/page/index')],
            [
                [$this->message('_missing', 'cased/Views/page/_missing'), 6],
            ],
        );
    }

    public function testRuleTreatsConfiguredViewPathAsViewsDirectory(): void
    {
        $this->analyse(
            [self::getDataFilePath('templates/site/index')],
            [
                [$this->message('_missing', 'templates/site/_missing'), 9],
                [$this->message('/site/_missing', 'templates/site/_missing'), 10],
                [$this->message('/layouts/missing', 'templates/layouts/missing'), 11],
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

    /**
     * @param bool $relative whether the path is built from an alias, which the test config sets relative to the project root
     */
    private function message(string $view, string $file, bool $relative = false): string
    {
        $path = $relative
            ? 'tests/Rules/Data/NestedViewExistenceValidation/' . $file . '.php'
            : self::getDataFilePath($file);

        return sprintf('View "%s" does not exist at "%s". Check the view name or create the view file.', $view, $path);
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
