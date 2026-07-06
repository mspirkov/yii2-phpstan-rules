<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii2 PHPStan rules</h1>
</p>

A set of PHPStan rules for Yii2 projects that I put together for my own day-to-day work. They check for a handful of things I personally try to avoid — business logic piling up in controllers, database access in views, `Yii::$app` being read and written from anywhere, model `rules()` arrays that look fine but aren't. In my experience they help keep a Yii2 codebase a bit cleaner and more maintainable, but they're just my opinions turned into checks, not a universal standard — use what's useful, ignore or disable the rest.

[![PHP](https://img.shields.io/badge/%3E%3D7.4-7A86B8.svg?style=for-the-badge&logo=php&logoColor=white&label=PHP)](https://www.php.net/releases/7_4_0.php)
[![Yii 2.0.x](https://img.shields.io/badge/%3E%3D2.0.53-247BA0.svg?style=for-the-badge&logo=yii&logoColor=white&label=Yii)](https://github.com/yiisoft/yii2/tree/2.0.53)
[![Tests](https://img.shields.io/github/actions/workflow/status/mspirkov/yii2-phpstan-rules/ci.yml?branch=main&style=for-the-badge&logo=github&label=Tests)](https://github.com/mspirkov/yii2-phpstan-rules/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/mspirkov/yii2-phpstan-rules/ci.yml?branch=main&style=for-the-badge&logo=github&label=PHPStan)](https://github.com/mspirkov/yii2-phpstan-rules/actions/workflows/ci.yml)
![Coverage](https://img.shields.io/badge/100%25-44CC11.svg?style=for-the-badge&label=Coverage)
![PHPStan Level Max](https://img.shields.io/badge/Max-7A86B8.svg?style=for-the-badge&label=PHPStan%20Level)

## What's inside

| Rule                                                                   | Catches                                                                                                                  |
| ---------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| [`noComplexControllerActions`](#complexity-limits)                     | Controller actions with too much branching/looping — logic that belongs in a service                                     |
| [`noComplexActionClasses`](#complexity-limits)                         | The same, for standalone `yii\base\Action` classes                                                                       |
| [`noControllerActionCallsViaThis`](#no-calling-actions-via-this)       | `$this->actionFoo()` inside a controller instead of a redirect or shared method                                          |
| [`noDbQueriesInControllers`](#no-database-access-outside-repositories) | Direct DB/ActiveRecord access in controllers                                                                             |
| [`noDbQueriesInActions`](#no-database-access-outside-repositories)     | Direct DB/ActiveRecord access in `Action` classes                                                                        |
| [`noDbQueriesInViews`](#no-database-access-outside-repositories)       | Direct DB/ActiveRecord access in view files                                                                              |
| [`noDynamicQueryWhere`](#no-dynamic-sql-strings)                       | String-concatenated conditions passed to `Query::where()` / `andWhere()`                                                 |
| [`noForbiddenYiiAppProperties`](#taming-yiiapp)                        | Reads of arbitrary `Yii::$app->*` components                                                                             |
| [`noYiiAppPropertyMutation`](#taming-yiiapp)                           | Writes to `Yii::$app` properties, including `setComponents()`                                                            |
| [`noDirectSuperglobals`](#no-raw-superglobals)                         | Direct use of `$_GET`, `$_POST`, `$_SESSION`, etc.                                                                       |
| [`modelRulesValidation`](#model-validation-rules-that-lie)             | Malformed or invalid `rules()` in `yii\base\Model` — unknown validators, missing required options, bad regexes, and more |

Every rule ships with its own PHPStan error identifier (`mspirkovYii2Rules.*`), so you can target `ignoreErrors` precisely instead of silencing a whole rule.

## Installation

```bash
php composer.phar require --dev mspirkov/yii2-phpstan-rules
```

If your project uses [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer), the rules are picked up automatically — nothing else to do.

Otherwise, include them manually in your `phpstan.neon`:

```neon
includes:
    - vendor/mspirkov/yii2-phpstan-rules/rules.neon
```

## Configuration

All rules are on by default. Turn the whole set off, or tune individual rules, under `parameters.mspirkovYii2Rules`:

```neon
parameters:
    mspirkovYii2Rules:
        enableAllRules: true

        # Component IDs treated as "the database" by the DB-access rules
        yiiAppDbProperties:
            - db

        # Thresholds for the complexity rules — exceeding any one flags the method
        actionComplexity:
            ifCount: 3
            foreachCount: 0
            forCount: 0
            whileCount: 0
            doWhileCount: 0
            switchCount: 0
            matchCount: 0
            ternaryCount: 1
            tryCatchCount: 1

        # Yii::$app properties allowed to be read anywhere (e.g. request-agnostic settings)
        noForbiddenYiiAppProperties:
            allowedProperties:
                - id
                - name
                - charset
                - language
                - timeZone

        # Project-specific model validator aliases
        modelRulesValidation:
            customValidators:
                slug: app\validators\SlugValidator

        # Disable a single rule without touching the rest
        noDynamicQueryWhere:
            enabled: false
```

## The rules

### Complexity limits

`noComplexControllerActions` and `noComplexActionClasses` count `if`, `foreach`, `for`, `while`, `do-while`, `switch`, `match`, ternaries, and `try/catch` blocks inside a controller action or `Action::run()`. Cross any configured threshold and the rule fires, pointing at the exact construct that pushed it over:

```php
// ✗ flagged: 4 `if` statements against a default limit of 3
public function actionCheckout()
{
    if ($cart->isEmpty()) { /* ... */ }
    if (!$user->hasPaymentMethod()) { /* ... */ }
    if ($stock->isLow($cart)) { /* ... */ }
    if ($coupon->isExpired()) { /* ... */ }
}

// ✓ the decision tree moves to a service, the action just orchestrates
public function actionCheckout()
{
    $this->checkoutService->process($cart, $user);
}
```

### No calling actions via `$this`

```php
// ✗ flagged: bypasses the action-resolution pipeline (filters, events, results)
public function actionEdit($id)
{
    // ...
    return $this->actionView($id);
}

// ✓ redirect, or extract the shared part into a private method / service
public function actionEdit($id)
{
    return $this->redirect(['view', 'id' => $id]);
}
```

### No database access outside repositories

Fires on `ActiveRecord::find()`/`findOne()`/`save()`, `Yii::$app->db`, `Yii::$app->db->createCommand()`, creating or configuring a `Query`, transactions, and friends — wherever they turn up in a controller, an `Action`, or a view file.

```php
// ✗ flagged in a view
<?php foreach (Post::find()->where(['status' => 1])->all() as $post): ?>

// ✓ the controller/action fetches the data, the view only renders it
<?php foreach ($posts as $post): ?>
```

`noDbQueriesInControllers` / `noDbQueriesInActions` push the same query building into a repository or service instead. Query builder setup counts too: `new Query()`, `$query->where()`, and dynamic calls on a `Query` object are all treated as direct database access in these layers.

### No dynamic SQL strings

```php
// ✗ flagged: string-built condition, one step from SQL injection
$query->where("status = $status");
$query->where('status = ' . $status);

// ✓ array condition syntax — parameterized, and PHPStan can see the shape
$query->where(['status' => $status]);
```

### Taming `Yii::$app`

Two rules keep the service locator from becoming a place where any property can be read or reassigned from anywhere:

```php
// ✗ noForbiddenYiiAppProperties: arbitrary component access
$cache = Yii::$app->cache;

// ✗ noYiiAppPropertyMutation: mutating the container at runtime
Yii::$app->params = [];
Yii::$app->setComponents([...]);

// ✓ inject the component instead
public function __construct(private CacheInterface $cache) {}
```

A short allowlist (`id`, `name`, `charset`, `language`, `timeZone` by default) stays available everywhere since those are effectively static configuration, not injectable services.

### No raw superglobals

```php
// ✗ flagged, with the fix suggested in the error message
$id = $_GET['id'];

// ✓
$id = Yii::$app->request->get('id');
```

Covers `$_GET`, `$_POST`, `$_REQUEST`, `$_SESSION`, `$_COOKIE`, `$_FILES`, and `$_SERVER`, each pointing at the matching `yii\web\Request` / `Session` / `UploadedFile` API.

### Model validation rules that lie

`Model::rules()` is just a plain array — PHP will never tell you that you forgot a validator's required option, wrote an invalid regex, or misconfigured one of its options. For every rule entry the validator type resolves to (a built-in alias like `required`/`string`/`number`/`compare`/`date`/`match`/`in`/`unique`/`exist`/`file`/`image`/`ip`/`url`, a custom `Validator` subclass, a configured project alias, or an inline closure/method), this rule statically checks the option array against what that validator actually accepts and requires. A validator name it can't resolve is reported as an error; add project-specific aliases under `modelRulesValidation.customValidators`:

```php
public function rules()
{
    return [
        ['email', 'exist', 'targetClass' => X::class],   // ✗ 'exist' requires 'targetAttribute'
        ['code', 'match', 'pattern' => '/[/'],           // ✗ invalid regular expression
        ['ip', 'ip', 'ipv4' => false, 'ipv6' => false],  // ✗ disables both protocols
        ['message', 'string', 'max' => 'invalid'],       // ✗ 'max' must be int|null
        ['status', 'someUnregisteredAlias'],             // ✗ unknown validator

        ['name', 'string', 'max' => 255],                // ✓
    ];
}
```
