<?php

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Data\ControllerBehaviorActionsValidation;

use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerBehaviorActionsValidation\BaseController;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerBehaviorActionsValidation\NotAccessRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerBehaviorActionsValidation\ProjectAccessRule;
use MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ControllerBehaviorActionsValidation\TraitController;
use stdClass;
use yii\base\Controller;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\filters\AjaxFilter;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use yii\filters\HostControl;
use yii\filters\HttpCache;
use yii\filters\PageCache;
use yii\filters\RateLimiter;
use yii\filters\VerbFilter;
use yii\web\ErrorAction;

final class ValidController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'view-post' => ['GET'],
                    'inherited' => ['GET'],
                    'from-base' => ['GET'],
                    'captcha' => ['GET'],
                    '*' => ['GET'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'view-post', 'inherited', 'from-base', 'captcha', 'view-*', '*'],
                'except' => ['error'],
                'rules' => [
                    ['allow' => true, 'actions' => ['index', 'view-post'], 'roles' => ['@']],
                    ['allow' => true, 'class' => ProjectAccessRule::class, 'actions' => ['captcha']],
                    ['allow' => false],
                ],
            ],
            'negotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['index'],
            ],
            'auth' => [
                'class' => HttpBearerAuth::class,
                'optional' => ['index', 'ind*'],
            ],
            'ajax' => ['class' => AjaxFilter::class, 'only' => ['index']],
            'hostControl' => ['class' => HostControl::class, 'allowedHosts' => ['example.com'], 'except' => ['error']],
            'httpCache' => ['class' => HttpCache::class, 'only' => ['index', 'view-post']],
            'pageCache' => ['class' => PageCache::class, 'only' => ['index'], 'except' => ['captcha']],
            'rateLimiter' => ['class' => RateLimiter::class, 'only' => ['from-base', 'inherited']],
            'cors' => ['class' => Cors::class, 'except' => ['index']],
        ];
    }

    public function actions(): array
    {
        return array_merge(parent::actions(), [
            'error' => ErrorAction::class,
            'captcha' => CaptchaAction::class,
        ]);
    }

    public function actionIndex(): void
    {
    }

    public function actionViewPost(): void
    {
    }
}

final class InvalidController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'delet' => ['POST'],
                    'viewpost' => ['GET'],
                    'ViewPost' => ['GET'],
                    'hidden' => ['GET'],
                    'spread' => ['GET'],
                    'plus' => ['GET'],
                    '*' => ['GET'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => ['missing', 'index', 'view_post'],
                'except' => ['also-missing', 'error'],
                'rules' => [
                    ['allow' => true, 'actions' => ['index', 'missing-in-rule'], 'roles' => ['@']],
                    ['allow' => true, 'actions' => ['*']],
                    ['allow' => true, 'class' => ProjectAccessRule::class, 'actions' => ['custom-missing']],
                ],
            ],
            'negotiator' => [
                'class' => ContentNegotiator::class,
                'only' => ['not-found'],
            ],
            'auth' => [
                'class' => HttpBearerAuth::class,
                'optional' => ['index', 'not-optional'],
            ],
            'ajax' => ['class' => AjaxFilter::class, 'only' => ['index', 'missing-ajax']],
            'hostControl' => ['class' => HostControl::class, 'allowedHosts' => ['example.com'], 'except' => ['missing-host']],
            'httpCache' => ['class' => HttpCache::class, 'only' => ['missing-http-cache']],
            'pageCache' => ['class' => PageCache::class, 'only' => ['missing-page-cache'], 'except' => ['error']],
            'rateLimiter' => ['class' => RateLimiter::class, 'only' => ['missing-rate-limiter']],
            'cors' => ['class' => Cors::class, 'except' => ['index', 'missing-cors']],
        ];
    }

    public function actions(): array
    {
        if ($this->id === 'spread') {
            return [...parent::actions(), 'spread' => ErrorAction::class];
        }

        if ($this->id === 'plus') {
            return parent::actions() + ['plus' => ErrorAction::class];
        }

        return ['error' => ErrorAction::class];
    }

    public function actionIndex(): void
    {
    }

    public function actionViewPost(): void
    {
    }

    protected function actionHidden(): void
    {
    }
}

final class SkippedController extends BaseController
{
    public string $filterClass = VerbFilter::class;

    /** @var list<string> */
    public array $dynamicActions = [];

    public function behaviors(): array
    {
        $dynamicList = ['dynamic-unpacked'];
        $notAConfig = 'skipped';

        return [
            'noClass' => ['only' => ['missing']],
            'dynamicClass' => ['class' => $this->filterClass, 'actions' => ['missing' => ['GET']]],
            'unknownClass' => ['class' => 'App\Filters\MissingFilter', 'only' => ['missing']],
            'notFilter' => ['class' => stdClass::class, 'only' => ['missing']],
            'notAnArray' => $notAConfig,
            ...['unpackedBehavior' => ['class' => VerbFilter::class, 'actions' => ['missing' => ['GET']]]],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => $this->dynamicActions,
            ],
            'notArrayActions' => [
                'class' => VerbFilter::class,
                'actions' => 'missing',
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => [...$dynamicList, $this->filterClass, 'index'],
                'except' => $this->dynamicActions,
                'rules' => [
                    ['allow' => true, 'class' => NotAccessRule::class, 'actions' => ['missing']],
                    ['allow' => true, 'class' => $this->filterClass, 'actions' => ['missing']],
                    ['allow' => true, 'class' => 'App\Filters\MissingRule', 'actions' => ['missing']],
                    ['allow' => true, 'actions' => $this->dynamicActions],
                    new AccessRule(['actions' => ['missing']]),
                    ...[['allow' => true, 'actions' => ['missing']]],
                ],
            ],
            'accessWithoutRules' => ['class' => AccessControl::class],
            'accessWithDynamicRules' => ['class' => AccessControl::class, 'rules' => $this->dynamicActions],
        ];
    }

    public function actionIndex(): void
    {
    }
}

abstract class AbstractSkippedController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['missing' => ['GET']]],
        ];
    }
}

final class TraitActionsController extends TraitController
{
    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['missing' => ['GET']]],
        ];
    }
}

final class VariableActionsController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['missing' => ['GET']]],
        ];
    }

    public function actions(): array
    {
        $actions = ['error' => ErrorAction::class];

        return $actions;
    }
}

final class DynamicKeyActionsController extends Controller
{
    public string $actionId = 'dynamic';

    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['missing' => ['GET']]],
        ];
    }

    public function actions(): array
    {
        return [
            $this->actionId => ErrorAction::class,
        ];
    }
}

final class DynamicSpreadActionsController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['missing' => ['GET']]],
        ];
    }

    public function actions(): array
    {
        return array_merge(parent::actions(), $this->dynamicActions(), [...$this->dynamicActions()]);
    }

    private function dynamicActions(): array
    {
        return [];
    }
}

final class NotController
{
    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['missing' => ['GET']]],
        ];
    }
}

final class OtherMethodController extends Controller
{
    public function rules(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['missing' => ['GET']]],
        ];
    }
}
