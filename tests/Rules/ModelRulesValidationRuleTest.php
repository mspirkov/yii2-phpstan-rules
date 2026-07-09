<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ModelRulesValidationRule;
use yii\validators\Validator;
use stdClass;

/**
 * @extends AbstractTestCase<ModelRulesValidationRule>
 */
final class ModelRulesValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $errors = [
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
            ['Unknown option "lenght" for validator MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelRulesValidation\ProjectSpecificValidator.', 63],
            ['Validator option "ipv4" for yii\validators\IpValidator must be bool, int given.', 128],
            ['Unknown option "current" for validator yii\validators\StringValidator.', 132],
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
            ['Unknown validator "missingValidatorAlias".', 215],
        ];

        // For versions 7.4 and 8.0, we simply do not display this error because PHPStan
        // cannot infer types with such precision.
        if (PHP_VERSION_ID >= 80100) {
            $errors[] = ['Model validation rule attributes must be a string or array of strings.', 239];
        }

        $this->analyse([self::getDataFilePath('code')], $errors);
    }

    /**
     * Covers the defensive fallback in resolveKnownValidatorClass() for a built-in
     * validator alias whose configured class is not a valid yii\validators\Validator,
     * which cannot happen with Yii's own Validator::$builtInValidators map but is
     * guarded against since that map is a mutable public static property.
     */
    public function testBrokenBuiltInValidatorClassIsReportedAsUnknownValidator(): void
    {
        $originalBuiltInValidators = Validator::$builtInValidators;
        Validator::$builtInValidators['brokenBuiltIn'] = stdClass::class;

        try {
            $this->analyse(
                [__DIR__ . '/Data/ModelRulesValidation/brokenBuiltInValidator.php'],
                [
                    ['Unknown validator "brokenBuiltIn".', 14],
                ],
            );
        } finally {
            Validator::$builtInValidators = $originalBuiltInValidators;
        }
    }

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            self::getConfigFilePath('config'),
        ]);
    }

    protected static function getRuleClass(): string
    {
        return ModelRulesValidationRule::class;
    }
}
