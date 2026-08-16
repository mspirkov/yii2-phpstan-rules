<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\BehaviorAttributesValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Data\BehaviorAttributesValidation\InvalidPost;

/**
 * @extends AbstractTestCase<BehaviorAttributesValidationRule>
 */
final class BehaviorAttributesValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [sprintf('Unknown attribute "createdAt" for model %s.', InvalidPost::class), 105],
                [sprintf('Unknown attribute "updatedAt" for model %s.', InvalidPost::class), 106],
                [sprintf('Unknown attribute "createdBy" for model %s.', InvalidPost::class), 110],
                [sprintf('Unknown attribute "updatedBy" for model %s.', InvalidPost::class), 111],
                [sprintf('Unknown attribute "titel" for model %s.', InvalidPost::class), 115],
                [sprintf('Unknown attribute "alias" for model %s.', InvalidPost::class), 116],
                [sprintf('Unknown attribute "viewsCount" for model %s.', InvalidPost::class), 121],
                [sprintf('Unknown attribute "createdAt" for model %s.', InvalidPost::class), 126],
                [sprintf('Unknown attribute "updatedAt" for model %s.', InvalidPost::class), 127],
                [sprintf('Unknown attribute "createdAt" for model %s.', InvalidPost::class), 132],
                [sprintf('Unknown attribute "updatedAt" for model %s.', InvalidPost::class), 132],
                [sprintf('Unknown attribute "createdBy" for model %s.', InvalidPost::class), 138],
                [sprintf('Unknown attribute "updatedBy" for model %s.', InvalidPost::class), 138],
                [sprintf('Unknown attribute "aliass" for model %s.', InvalidPost::class), 144],
                [sprintf('Unknown attribute "createdAt" for model %s.', InvalidPost::class), 150],
                [sprintf('Unknown attribute "createdAt" for model %s.', InvalidPost::class), 156],
                [sprintf('Unknown attribute "updatedAt" for model %s.', InvalidPost::class), 156],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return BehaviorAttributesValidationRule::class;
    }
}
