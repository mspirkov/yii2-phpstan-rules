<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\NoControllerActionCallsViaThisRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoControllerActionCallsViaThisRule>
 */
final class NoControllerActionCallsViaThisRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/Data/NoControllerActionCallsViaThis/code.php'],
            [
                ['Calling controller action actionIndex() via $this is forbidden. Move shared logic to a service or a private method, or perform a redirect.', 15],
                ['Calling controller action actionView() via $this is forbidden. Move shared logic to a service or a private method, or perform a redirect.', 26],
            ],
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../../rules.neon',
        ]);
    }

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoControllerActionCallsViaThisRule::class);
    }
}
