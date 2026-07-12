<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\WidgetPropertiesValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\WidgetPropertiesValidation\MyWidget;

/**
 * @extends AbstractTestCase<WidgetPropertiesValidationRule>
 */
final class WidgetPropertiesValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown option "unknownOption" for widget %s.', MyWidget::class), 19],
                [sprintf('Widget option "limit" for %s must be int, string given.', MyWidget::class), 26],
                ['Widget configuration option keys must be strings.', 43],
                [sprintf('Unknown option "unknownOption" for widget %s.', MyWidget::class), 50],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return WidgetPropertiesValidationRule::class;
    }
}
