<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ComponentBehaviorsValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ComponentBehaviorsValidation\NotBehavior;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ComponentBehaviorsValidation\ProjectBehavior;

/**
 * @extends AbstractTestCase<ComponentBehaviorsValidationRule>
 */
final class ComponentBehaviorsValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $notBehaviorClass = NotBehavior::class;
        $projectBehaviorClass = ProjectBehavior::class;

        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Component behavior class "%s" must be yii\base\Behavior or its subclass.', $notBehaviorClass), 65],
                [sprintf('Component behavior class "%s" must be yii\base\Behavior or its subclass.', $notBehaviorClass), 66],
                ['Component behavior configuration option keys must be strings.', 67],
                [sprintf('Unknown option "unknown" for behavior %s.', $projectBehaviorClass), 68],
                [sprintf('Unknown option "on beforeValidate" for behavior %s.', $projectBehaviorClass), 69],
                [sprintf('Unknown option "as nested" for behavior %s.', $projectBehaviorClass), 71],
                [sprintf('Behavior option "enabled" for %s must be bool, int given.', $projectBehaviorClass), 72],
                [sprintf('Behavior option "enabled" for %s must be bool, string given.', $projectBehaviorClass), 73],
                [sprintf('Behavior option "threshold" for %s must be int, string given.', $projectBehaviorClass), 74],
                [sprintf('Behavior option "enabled" for %s must be bool, int given.', $projectBehaviorClass), 84],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ComponentBehaviorsValidationRule::class;
    }
}
