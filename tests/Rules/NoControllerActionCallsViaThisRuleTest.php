<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoControllerActionCallsViaThisRule;

/**
 * @extends AbstractTestCase<NoControllerActionCallsViaThisRule>
 */
final class NoControllerActionCallsViaThisRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                ['Calling controller action actionIndex() via $this is forbidden. Move shared logic to a service or a private method, or perform a redirect.', 15],
                ['Calling controller action actionView() via $this is forbidden. Move shared logic to a service or a private method, or perform a redirect.', 26],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return NoControllerActionCallsViaThisRule::class;
    }
}
