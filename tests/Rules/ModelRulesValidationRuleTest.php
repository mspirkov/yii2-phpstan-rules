<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ModelRulesValidationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ModelRulesValidationRule>
 */
final class ModelRulesValidationRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/ModelRulesValidation/code.php'],
            [
                ['Yii model validation rule must specify validator type at index 1.', 43],
                ['Yii model validation rule attribute names must be a string or an array of strings.', 44],
                ['Yii model validation rule attributes must be strings.', 45],
                ['Yii model validation rule contains an empty attribute name.', 46],
                ['Yii model validation rule validator type must be a string or Closure.', 47],
                ['Yii model validation rule option keys must be strings.', 48],
                ['Unknown option "lenght" for Yii validator yii\validators\StringValidator.', 49],
                ['Yii validator "filter" requires option "filter".', 50],
                ['Yii validator "in" requires option "range".', 51],
                ['Yii validator "match" requires option "pattern".', 52],
                ['Yii validator "each" requires option "rule".', 53],
                ['Unknown compare validator operator "<>".', 54],
                ['Unknown date validator type "week".', 55],
                ['Yii IP validator cannot disable both IPv4 and IPv6 checks.', 56],
                ['Yii match validator option "pattern" contains an invalid regular expression "[.".', 57],
                ['Yii "in" validator option "range" must be an array, Closure, or Traversable.', 58],
                ['Embedded Yii validation rule must specify validator type at index 0.', 59],
                ['Unknown option "lenght" for Yii validator yii\validators\StringValidator.', 60],
                ['Yii validator option "on" must contain only scenario names as strings.', 61],
                ['Invalid Yii model validation rule: each rule must be an array or an instance of yii\validators\Validator.', 62],
                ['Yii model validation rule must specify attribute names at index 0.', 170],
                ['Yii model validation rule attribute names at index 0 cannot be null.', 171],
                ['Yii model validation rule validator type at index 1 cannot be null.', 172],
                ['Embedded Yii validation rule validator type at index 0 cannot be null.', 173],
                ['Yii model validation rule contains an empty attribute name.', 174],
                ['Yii match validator option "pattern" must be a string.', 175],
                ['Yii validator option "on" must be a string or an array of strings.', 176],
                ['Yii model validation rule must specify attribute names at index 0.', 177],
                ['Yii model validation rule must specify validator type at index 1.', 177],
                ['Yii model validation rule must specify validator type at index 1.', 187],
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
        return self::getContainer()->getByType(ModelRulesValidationRule::class);
    }
}
