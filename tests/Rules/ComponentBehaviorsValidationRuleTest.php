<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ComponentBehaviorsValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ComponentBehaviorsValidation\NotBehavior;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ComponentBehaviorsValidation\ProjectBehavior;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ComponentBehaviorsValidationRule>
 */
final class ComponentBehaviorsValidationRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $notBehaviorClass = NotBehavior::class;
        $projectBehaviorClass = ProjectBehavior::class;

        $this->analyse(
            [__DIR__ . '/Data/ComponentBehaviorsValidation/code.php'],
            [
                ['Component behavior must be a class string, configuration array, or yii\base\Behavior instance.', 64],
                ['Component behavior must be a class string, configuration array, or yii\base\Behavior instance.', 65],
                [sprintf('Component behavior class "%s" must be yii\base\Behavior or its subclass.', $notBehaviorClass), 68],
                ['Unknown behavior class "MissingBehavior".', 69],
                ['Component behavior configuration must specify "class" or "__class".', 70],
                ['Component behavior class cannot be null.', 71],
                ['Component behavior class must be a string.', 72],
                [sprintf('Component behavior class "%s" must be yii\base\Behavior or its subclass.', $notBehaviorClass), 73],
                ['Unknown behavior class "MissingBehavior".', 74],
                ['Component behavior configuration option keys must be strings.', 75],
                [sprintf('Unknown option "unknown" for behavior %s.', $projectBehaviorClass), 76],
                [sprintf('Behavior option "enabled" for %s must be bool, int given.', $projectBehaviorClass), 77],
                [sprintf('Behavior option "enabled" for %s must be bool, string given.', $projectBehaviorClass), 78],
                [sprintf('Behavior option "threshold" for %s must be int, string given.', $projectBehaviorClass), 79],
                [sprintf('Behavior option "enabled" for %s must be bool, int given.', $projectBehaviorClass), 89],
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
        return self::getContainer()->getByType(ComponentBehaviorsValidationRule::class);
    }
}
