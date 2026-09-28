<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ControllerViewExistenceValidationRule;

/**
 * @extends AbstractTestCase<ControllerViewExistenceValidationRule>
 */
final class ControllerViewExistenceValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        // The namespace of this class doesn't match its directory, so PSR-4 can't autoload it. Load it explicitly
        // so that PHPStan can reflect it and the rule is skipped because of the location, not a missing class.
        require_once self::getDataFilePath('misplaced/MisplacedController');

        $this->analyse(
            [
                self::getDataFilePath('controllers/SiteController'),
                self::getDataFilePath('controllers/SkippedController'),
                self::getDataFilePath('controllers/UserProfileController'),
                self::getDataFilePath('controllers/admin/UserController'),
                self::getDataFilePath('controllers/TwigController'),
                self::getDataFilePath('controllers/BaseController'),
                self::getDataFilePath('controllers/OverriddenViewPathController'),
                self::getDataFilePath('controllers/LandingPageHandler'),
                self::getDataFilePath('controllers/Controller'),
                self::getDataFilePath('NoViewsModule/controllers/EmptyController'),
                self::getDataFilePath('misplaced/MisplacedController'),
                self::getDataFilePath('custom/LegacyController'),
                self::getDataFilePath('CasedModule/Controllers/PageController'),
                self::getDataFilePath('Themed/HomeController'),
                self::getDataFilePath('Themed/admin/UserController'),
                self::getDataFilePath('Themed/Special/ReportController'),
                self::getDataFilePath('ThemedOther/FooController'),
                self::getDataFilePath('Plain/PageController'),
            ],
            [
                [$this->message('missing'), 12],
                [$this->message('missing'), 12],
                [$this->message('missing'), 12],
                [$this->message('missing'), 13],
                [$this->message('index'), 11],
                [$this->message('missing'), 27],
                [$this->message('_missing'), 28],
                [$this->message('missing'), 29],
                [$this->message('/site/missing'), 30],
                [$this->message('missing.php'), 31],
                [$this->message('sub/missing'), 32],
                [$this->message('@app/views/shared/missing'), 33, $this->tip('@app')],
                [$this->message('@shared/missing'), 34, $this->tip('@shared')],
                [$this->message('//layouts/missing'), 35, $this->tip('@app')],
                [$this->unresolved('@unknown/missing', '@unknown'), 36],
                [$this->message('missing'), 37],
                [$this->message('missing'), 40],
                [$this->message('missing'), 12],
                [$this->message('missing'), 12],
                [$this->message('missing'), 12],
                [$this->message('missing'), 12],
            ],
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
        return ControllerViewExistenceValidationRule::class;
    }

    private function message(string $view): string
    {
        return sprintf('View "%s" does not exist. Check the view name or create the view file.', $view);
    }

    private function tip(string $alias): string
    {
        return sprintf(
            'The view path is built from the "%s" alias set in the "mspirkovYii2Rules.aliases" parameter. It may point to the wrong directory.',
            $alias
        );
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
