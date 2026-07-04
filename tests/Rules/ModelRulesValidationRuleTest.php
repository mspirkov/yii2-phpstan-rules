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
                ['Model validation rule must specify validator type at index 1.', 43],
                ['Model validation rule attributes must be a string or array of strings.', 44],
                ['Model validation rule attributes must be strings.', 45],
                ['Model validation rule contains an empty attribute name.', 46],
                ['Model validation rule validator type must be a string or Closure.', 47],
                ['Model validation rule option keys must be strings.', 48],
                ['Unknown option "lenght" for validator yii\validators\StringValidator.', 49],
                ['Validator "filter" requires option "filter".', 50],
                ['Validator "in" requires option "range".', 51],
                ['Validator "match" requires option "pattern".', 52],
                ['Validator "each" requires option "rule".', 53],
                ['Unknown compare validator operator "<>".', 54],
                ['Unknown date validator type "week".', 55],
                ['IP validator cannot disable both IPv4 and IPv6 checks.', 56],
                ['Match validator option "pattern" has an invalid regular expression "[.".', 57],
                ['"in" validator option "range" must be an array, Closure, or Traversable.', 58],
                ['Embedded validation rule must specify validator type at index 0.', 59],
                ['Unknown option "lenght" for validator yii\validators\StringValidator.', 60],
                ['Validator option "on" must contain only scenario names as strings.', 61],
                ['Model validation rule must be an array or a yii\validators\Validator instance.', 62],
                ['Validator option "ipv4" for yii\validators\IpValidator must be bool, int given.', 128],
                ['Model validation rule must specify attribute names at index 0.', 170],
                ['Model validation rule attribute names at index 0 cannot be null.', 171],
                ['Model validation rule validator type at index 1 cannot be null.', 172],
                ['Embedded validation rule validator type at index 0 cannot be null.', 173],
                ['Model validation rule contains an empty attribute name.', 174],
                ['Match validator option "pattern" must be a string.', 175],
                ['Validator option "on" must be a string or array of strings.', 176],
                ['Model validation rule must specify attribute names at index 0.', 177],
                ['Model validation rule must specify validator type at index 1.', 177],
                ['Model validation rule must specify validator type at index 1.', 187],
                ['Validator option "max" for yii\validators\StringValidator must be int|null, string given.', 212],
                ['Validator option "integerOnly" for yii\validators\NumberValidator must be bool, string given.', 213],
                ['Unknown option "attributeNames" for validator yii\validators\RequiredValidator.', 214],
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
