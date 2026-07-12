<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoRedundantHtmlEncodeRule;

/**
 * @extends AbstractTestCase<NoRedundantHtmlEncodeRule>
 */
final class NoRedundantHtmlEncodeRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Html::encode() call is redundant here. Its argument can never contain characters that need HTML-entity escaping.', 14],
                ['Html::encode() call is redundant here. Its argument can never contain characters that need HTML-entity escaping.', 20],
                ['Html::encode() call is redundant here. Its argument can never contain characters that need HTML-entity escaping.', 33],
                ['Html::encode() call is redundant here. Its argument can never contain characters that need HTML-entity escaping.', 41],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoRedundantHtmlEncodeRule::class;
    }
}
