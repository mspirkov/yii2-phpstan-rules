<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ViewRenderExistenceValidationRule;

/**
 * @extends AbstractTestCase<ViewRenderExistenceValidationRule>
 */
final class ViewRenderExistenceValidationRuleTest extends AbstractTestCase
{
    private const TIP = 'The view path is built from the "@app" alias set in the "mspirkovYii2Rules.aliases" parameter. It may point to the wrong directory.';

    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [$this->message('@app/views/layouts/missing'), 13, self::TIP],
                [$this->message('//layouts/missing'), 14, self::TIP],
                [$this->message('@app/views/layouts/missing'), 15, self::TIP],
                [$this->unresolved('@unknown/missing', '@unknown'), 16],
            ],
        );
    }

    public function testRuleSkipsViewFiles(): void
    {
        $this->analyse(
            [self::getDataFilePath('views/site/index')],
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
        return ViewRenderExistenceValidationRule::class;
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
