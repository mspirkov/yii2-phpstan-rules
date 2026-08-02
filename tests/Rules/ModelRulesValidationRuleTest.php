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
            ['Model validation rule must specify validator type at index 1.', 54],
            ['Model validation rule attributes must be a string or array of strings.', 55],
            ['Model validation rule attributes must be strings.', 56],
            ['Model validation rule contains an empty attribute name.', 57],
            ['Model validation rule validator type must be a string or Closure.', 58],
            ['Model validation rule option keys must be strings.', 59],
            ['Unknown option "lenght" for validator yii\validators\StringValidator.', 60],
            ['Validator "filter" requires option "filter".', 61],
            ['Validator "in" requires option "range".', 62],
            ['Validator "match" requires option "pattern".', 63],
            ['Validator "each" requires option "rule".', 64],
            ['Unknown compare validator operator "<>".', 65],
            ['Unknown date validator type "week".', 66],
            ['IP validator cannot disable both IPv4 and IPv6 checks.', 67],
            ['Match validator option "pattern" has an invalid regular expression "[.".', 68],
            ['"in" validator option "range" must be an array, Closure, or Traversable.', 69],
            ['Embedded validation rule must specify validator type at index 0.', 70],
            ['Unknown option "lenght" for validator yii\validators\StringValidator.', 71],
            ['Validator option "on" must contain only scenario names as strings.', 72],
            ['Model validation rule must be an array or a yii\validators\Validator instance.', 73],
            ['Unknown option "lenght" for validator MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelRulesValidation\ProjectSpecificValidator.', 74],
            ['Validator option "ipv4" for yii\validators\IpValidator must be bool, int given.', 142],
            ['Unknown option "current" for validator yii\validators\StringValidator.', 146],
            ['Model validation rule must specify attribute names at index 0.', 187],
            ['Model validation rule attribute names at index 0 cannot be null.', 188],
            ['Model validation rule validator type at index 1 cannot be null.', 189],
            ['Embedded validation rule validator type at index 0 cannot be null.', 190],
            ['Model validation rule contains an empty attribute name.', 191],
            ['Match validator option "pattern" must be a string.', 192],
            ['Validator option "on" must be a string or array of strings.', 193],
            ['Model validation rule must specify attribute names at index 0.', 194],
            ['Model validation rule must specify validator type at index 1.', 194],
            ['Model validation rule must specify validator type at index 1.', 206],
            ['Validator option "max" for yii\validators\StringValidator must be int|null, string given.', 236],
            ['Validator option "integerOnly" for yii\validators\NumberValidator must be bool, string given.', 237],
            ['Unknown option "attributeNames" for validator yii\validators\RequiredValidator.', 238],
            ['Unknown validator "missingValidatorAlias".', 239],
            ['Unknown attribute "nickname" for model MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelRulesValidation\UnknownAttributeModel.', 278],
            ['Unknown attribute "nickname" for model MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelRulesValidation\UnknownAttributeModel.', 279],
            ['Unknown attribute " login " for model MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelRulesValidation\AttributeNameShapeModel.', 315],
            ['Unknown attribute " nickname " for model MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ModelRulesValidation\AttributeNameShapeModel.', 316],
        ];

        // For versions 7.4 and 8.0, we simply do not display this error because PHPStan
        // cannot infer types with such precision.
        if (PHP_VERSION_ID >= 80100) {
            $errors[] = ['Model validation rule attributes must be a string or array of strings.', 265];
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
