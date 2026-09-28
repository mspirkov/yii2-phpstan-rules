<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Analyzers;

use MSpirkov\Yii2\PHPStan\Analyzers\ViewFileAnalyzer;
use PHPStan\Testing\PHPStanTestCase;

final class ViewFileAnalyzerTest extends PHPStanTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../../rules.neon',
        ]);
    }

    public function testRelativeViewOutsideViewsDirectoryIsSkipped(): void
    {
        self::assertNull($this->getAnalyzer()->findRenderedViewProblem('/tmp/no-such-directory/page.php', '_missing'));
    }

    private function getAnalyzer(): ViewFileAnalyzer
    {
        return self::getContainer()->getByType(ViewFileAnalyzer::class);
    }
}
