<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules;

use MSpirkov\Yii2\PHPStan\Rules\ControllerBehaviorActionsValidationRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerBehaviorActionsValidation\InvalidController;

/**
 * @extends AbstractTestCase<ControllerBehaviorActionsValidationRule>
 */
final class ControllerBehaviorActionsValidationRuleTest extends AbstractTestCase
{
    public function testRule(): void
    {
        $this->analyse(
            [self::getDataFilePath('code')],
            [
                [$this->actionError('delet', 'the "actions" option of yii\filters\VerbFilter'), 94],
                [$this->actionError('viewpost', 'the "actions" option of yii\filters\VerbFilter'), 95],
                [$this->actionError('ViewPost', 'the "actions" option of yii\filters\VerbFilter'), 96],
                [$this->actionError('hidden', 'the "actions" option of yii\filters\VerbFilter'), 97],
                [$this->actionError('missing', 'the "only" option of yii\filters\AccessControl'), 105],
                [$this->actionError('view_post', 'the "only" option of yii\filters\AccessControl'), 105],
                [$this->actionError('also-missing', 'the "except" option of yii\filters\AccessControl'), 106],
                [$this->actionError('missing-in-rule', 'an access rule of yii\filters\AccessControl'), 108],
                [$this->actionError('*', 'an access rule of yii\filters\AccessControl'), 109],
                [$this->actionError('custom-missing', 'an access rule of yii\filters\AccessControl'), 110],
                [$this->actionError('not-found', 'the "only" option of yii\filters\ContentNegotiator'), 115],
                [$this->actionError('not-optional', 'the "optional" option of yii\filters\auth\HttpBearerAuth'), 119],
                [$this->actionError('missing-ajax', 'the "only" option of yii\filters\AjaxFilter'), 121],
                [$this->actionError('missing-host', 'the "except" option of yii\filters\HostControl'), 122],
                [$this->actionError('missing-http-cache', 'the "only" option of yii\filters\HttpCache'), 123],
                [$this->actionError('missing-page-cache', 'the "only" option of yii\filters\PageCache'), 124],
                [$this->actionError('missing-rate-limiter', 'the "only" option of yii\filters\RateLimiter'), 125],
                [$this->actionError('missing-cors', 'the "except" option of yii\filters\Cors'), 126],
            ],
        );
    }

    protected static function getRuleClass(): string
    {
        return ControllerBehaviorActionsValidationRule::class;
    }

    private function actionError(string $actionId, string $reference): string
    {
        return sprintf(
            'Action "%s" referenced in %s does not exist in controller %s.',
            $actionId,
            $reference,
            InvalidController::class
        );
    }
}
